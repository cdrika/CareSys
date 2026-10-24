<?php
namespace App\Services;

class RecruitmentService
{
    public function __construct(public Workspace $w) {}

    public function saveProfile(array $d): array
    {
        return $this->w->transaction(function() use($d) {
            $p=$this->w->ownPerson();
            $row=$this->w->db->query('SELECT * FROM cs_profiles WHERE person_id=? FOR UPDATE',[$p['id']])->getRowArray();
            $regions=array_values(array_unique(array_filter(array_map('trim',explode(',',Workspace::text($d,'regions',1400,false))))));
            if(count($regions)>20)throw new DomainError('Choose up to 20 preferred work areas.');
            foreach ($regions as &$region){$place=\App\Libraries\GeographyCatalog::resolve($region);if(!$place)throw new DomainError('Choose a valid preferred work area using the location picker.');$region=$place['code'];}unset($region);
            $regions=array_values(array_unique($regions));
            $values=['role'=>Workspace::text($d,'role',100,false),'summary'=>Workspace::text($d,'summary',3000,false),'skills'=>Workspace::text($d,'skills',500,false),'qualifications'=>Workspace::text($d,'qualifications',500,false),'work_type'=>Workspace::text($d,'work_type',40),'availability'=>Workspace::text($d,'availability',200,false),'availability_at'=>gmdate('Y-m-d H:i:s'),'preferences'=>json_encode(['travel_radius'=>Workspace::text($d,'travel_radius',50,false),'languages'=>Workspace::text($d,'languages',200,false),'care_settings'=>Workspace::text($d,'care_settings',200,false)])];
            // Editing published fields returns to private until the exact new preview is approved.
            $values['visibility']='private';
            if(!in_array($values['work_type'],['full_time','part_time','casual','contract'],true))throw new DomainError('Invalid employment preference.');
            $result=$row ? $this->w->update('profiles',$row,$values,(int)($d['version']??0)) : $this->w->insert('profiles',$values+['person_id'=>$p['id']]);
            $this->w->db->table('cs_profile_regions')->where('person_id',$p['id'])->delete();
            foreach($regions as $region) $this->w->insert('profile_regions',['person_id'=>$p['id'],'region'=>$region]);
            $this->w->audit('profile.saved',$result['ref'],(int)$p['id']);
            return $result;
        });
    }

    public function visibility(array $d): void
    {
        $this->w->transaction(function() use($d) {
            $p=$this->w->ownPerson();
            $row=$this->w->db->query('SELECT * FROM cs_profiles WHERE person_id=? FOR UPDATE',[$p['id']])->getRowArray();
            if(!$row) throw new DomainError('Save your profile first.');
            $state=Workspace::text($d,'visibility',24);
            if(!in_array($state,['discoverable','paused'],true)) throw new DomainError('Choose publish or pause.');
            if($state==='discoverable') {
                if(!$p['verified'] || !$row['role'] || !$row['summary'] || !$row['availability'] || !$this->w->db->table('cs_profile_regions')->where('person_id',$p['id'])->countAllResults() || ($d['confirm']??'')!=='yes') throw new DomainError('Verify your account, complete the profile and confirm the preview before publishing.');
                if($this->w->db->table('cs_employments')->where(['person_id'=>$p['id'],'state'=>'active'])->countAllResults()) throw new DomainError('Discovery while employed awaits the owner policy. Your personal account remains available.',409);
            }
            $this->w->update('profiles',$row,['visibility'=>$state],(int)($d['version']??0));
            $this->w->audit('profile.'.$state,$row['ref'],(int)$p['id']);
        });
    }

    public function search(array $d): array
    {
        $this->w->require('recruitment');
        $purpose=Workspace::text($d,'purpose',300);
        $q=$this->w->db->table('cs_profiles p')->select('people.ref,people.preferred_name,people.name,p.role,p.summary,p.skills,p.qualifications,p.work_type,p.availability,p.availability_at')->join('cs_people people','people.id=p.person_id')->where('p.visibility','discoverable')->where('people.verified',1)->where('NOT EXISTS (SELECT 1 FROM cs_employments e WHERE e.person_id=people.id AND e.state=\'active\')',null,false);
        if(!empty($d['name'])) $q->like('people.name',Workspace::text($d,'name',100));
        if(!empty($d['region'])) {
            $region=\App\Libraries\GeographyCatalog::resolve(Workspace::text($d,'region',64));if(!$region)throw new DomainError('Choose a valid work area.');
            $codes=[$region['code']];foreach(['state','county','country'] as $level)if(!empty($region[$level]))$codes[]=$region[$level];
            $aliases=['US-CA-067'=>'US-CA-SAC','US-CA-113'=>'US-CA-YOLO'];foreach($codes as $code)if(isset($aliases[$code]))$codes[]=$aliases[$code];
            $q->whereIn('p.person_id',function($b)use($codes,$region){$b->select('person_id')->from('cs_profile_regions')->groupStart()->whereIn('region',array_unique($codes));if($region['level']!=='city')$b->orLike('region',$region['code'].'-','after');$b->groupEnd();});
        }
        if(!empty($d['role'])) $q->like('p.role',Workspace::text($d,'role',100));
        if(!empty($d['skill'])) $q->like('p.skills',Workspace::text($d,'skill',100));
        if(!empty($d['qualification'])) $q->like('p.qualifications',Workspace::text($d,'qualification',100));
        if(!empty($d['work_type'])) $q->where('p.work_type',Workspace::text($d,'work_type',40));
        $rows=$q->orderBy('people.name')->limit(25,min(10000,max(0,(int)($d['page']??0)))*25)->get()->getResultArray();
        foreach($rows as &$r) {
            $person=$this->w->db->table('cs_people')->where('ref',$r['ref'])->get()->getRowArray();
            $r['regions']=implode(', ',array_column($this->w->db->table('cs_profile_regions')->where('person_id',$person['id'])->get()->getResultArray(),'region'));
            $r['relationship']='No current agency relationship recorded';
            $r['qualification_status']='Self-declared summary; credential validation required';
        }
        $this->w->audit('recruitment.search',null,null,'Purpose: '.$purpose.'; approved summaries only');
        return $rows;
    }

    public function career(array $d): array
    {
        return $this->w->transaction(function() use($d){
            $p=$this->w->ownPerson();
            foreach(['start_period','end_period'] as $key) if(!empty($d[$key]) && !preg_match('/^\d{4}(-(?:0[1-9]|1[0-2]))?$/D',$d[$key])) throw new DomainError('Career dates use YYYY or YYYY-MM, or may be blank.');
            if(!empty($d['start_period']) && !empty($d['end_period']) && $d['start_period']>$d['end_period']) throw new DomainError('End period must follow start period.');
            $entry=$this->w->insert('career',['person_id'=>$p['id'],'employer'=>Workspace::text($d,'employer',180),'role'=>Workspace::text($d,'role',100),'start_period'=>Workspace::text($d,'start_period',10,false),'end_period'=>Workspace::text($d,'end_period',10,false),'precision_code'=>'partial','summary'=>Workspace::text($d,'summary',3000),'source'=>'self_declared']);
            $this->w->audit('career.declared',$entry['ref'],(int)$p['id']); return $entry;
        });
    }

    public function invite(array $d): array
    {
        return $this->w->transaction(function()use($d){
            $this->w->require('recruitment');
            $email=Workspace::text($d,'email',254);
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainError('Enter a valid email.');
            $entry=$this->w->insert('invitations',['agency_id'=>$this->w->agency['id'],'recipient_email'=>$email,'purpose'=>Workspace::text($d,'purpose',300),'expires_at'=>gmdate('Y-m-d H:i:s',time()+7*86400)]);
            $this->w->audit('invitation.requested',$entry['ref']);
            $this->w->notify('invitation.delivery_pending',$entry['ref']);
            return ['ref'=>$entry['ref'],'state'=>'pending','message'=>'Invitation recorded. Delivery awaits the mail provider; existing account details are not disclosed.'];
        });
    }

    public function invitationDecision(array $d): void
    {
        $this->w->transaction(function()use($d){
            $person=$this->w->ownPerson();
            $row=$this->w->db->query('SELECT * FROM cs_invitations WHERE ref=? FOR UPDATE',[$d['ref']??''])->getRowArray();
            $email=auth()->user()->email;
            if(!$row || strcasecmp($row['recipient_email'],$email)!==0) throw new DomainError('Invitation unavailable.',404);
            if($row['state']!=='pending' || strtotime($row['expires_at'].' UTC')<=time()) throw new DomainError('Invitation is no longer pending.',409);
            $state=($d['decision']??'')==='accept'?'accepted':'declined';
            $this->w->update('invitations',$row,['state'=>$state,'person_id'=>$state==='accepted'?$person['id']:null],(int)$d['version']);
            $this->w->audit('invitation.'.$state,$row['ref'],(int)$person['id']);
        });
    }

    public function vacancy(array $d): array
    {
        $this->w->require('recruitment');
        $result=$this->w->insert('vacancies',['agency_id'=>$this->w->agency['id'],'title'=>Workspace::text($d,'title',140),'region'=>Workspace::text($d,'region',64),'work_type'=>Workspace::text($d,'work_type',40),'requirements'=>Workspace::text($d,'requirements',500,false),'closing_date'=>Workspace::date($d['closing_date']??'')]);
        $this->w->audit('vacancy.created',$result['ref']); return $result;
    }

    public function apply(array $d): array
    {
        return $this->w->transaction(function()use($d){
            $p=$this->w->ownPerson();
            $vacancy=$this->w->db->query('SELECT v.* FROM cs_vacancies v JOIN cs_agencies a ON a.id=v.agency_id WHERE v.ref=? AND v.state=\'open\' AND a.verification=\'verified\' FOR UPDATE',[$d['ref']??''])->getRowArray();
            if(!$vacancy || $vacancy['closing_date']<gmdate('Y-m-d')) throw new DomainError('Vacancy unavailable.',404);
            if($this->w->db->table('cs_applications')->where(['vacancy_id'=>$vacancy['id'],'person_id'=>$p['id']])->countAllResults()) throw new DomainError('You already have an application for this vacancy.',409);
            $r=$this->w->insert('applications',['agency_id'=>$vacancy['agency_id'],'vacancy_id'=>$vacancy['id'],'person_id'=>$p['id']]);
            $this->w->audit('application.submitted',$r['ref'],(int)$p['id']);return $r;
        });
    }

    public function stage(array $d): void
    {
        $this->w->transaction(function()use($d){
            if($this->w->agency) { $this->w->require('recruitment'); $r=$this->w->record('applications',$d['ref'],true); }
            else { $r=$this->w->db->query('SELECT * FROM cs_applications WHERE ref=? AND person_id=? FOR UPDATE',[$d['ref'],$this->w->ownPerson()['id']])->getRowArray(); }
            if(!$r) throw new DomainError('Application unavailable.',404);
            if(in_array($r['stage'],['withdrawn','declined','unsuccessful','cancelled','expired','employed'],true)) throw new DomainError('This application is closed.',409);
            $allowed=$this->w->agency ? ['reviewing','interview','checks_pending','unsuccessful','cancelled'] : ['withdrawn'];
            if(!in_array($d['stage']??'', $allowed,true)) throw new DomainError('Invalid recruitment transition.');
            $this->w->update('applications',$r,['stage'=>$d['stage']],(int)$d['version']);
            if($d['stage']==='withdrawn' || $d['stage']==='cancelled') {
                $this->w->db->query('SELECT id FROM cs_people WHERE id=? FOR UPDATE',[$r['person_id']]);
                $contracts=$this->w->db->table('cs_contracts')->where('application_id',$r['id'])->get()->getResultArray();
                foreach($contracts as $contract){
                    $joining=$this->w->db->query('SELECT * FROM cs_joining WHERE contract_id=? FOR UPDATE',[$contract['id']])->getRowArray();
                    if($joining && $joining['state']==='activated') throw new DomainError('Employment has started; use offboarding.',409);
                    if($joining) $this->w->update('joining',$joining,['state'=>'cancelled']);
                    $this->w->update('contracts',$contract,['state'=>'cancelled']);
                }
            }
            $this->w->audit('application.'.$d['stage'],$r['ref'],(int)$r['person_id']);
        });
    }

    public function requestHistory(array $d): array
    {
        return $this->w->transaction(function()use($d){
            $this->w->require('history');
            $p=$this->w->db->table('cs_people')->where('ref',$d['person_ref']??'')->get()->getRowArray();
            if(!$p || $p['identity_status']!=='resolved') throw new DomainError('Candidate identity confirmation required.',409);
            // Candidate-initiated application or accepted private invitation confirms the intended account.
            $confirmed=$this->w->table('applications')->where('person_id',$p['id'])->whereNotIn('stage',['withdrawn','cancelled'])->countAllResults()
                +$this->w->table('invitations')->where(['person_id'=>$p['id'],'state'=>'accepted'])->countAllResults();
            if(!$confirmed) throw new DomainError('The candidate must first apply or accept an invitation.',409);
            $days=(int)($d['days']??7); if($days<1 || $days>30) throw new DomainError('Request access for 1 to 30 days.');
            $r=$this->w->insert('history_requests',['agency_id'=>$this->w->agency['id'],'person_id'=>$p['id'],'recipient_membership_id'=>$this->w->membership['id'],'purpose'=>Workspace::text($d,'purpose',500),'categories'=>json_encode(['career_summary']),'entry_ids'=>'[]','expires_at'=>gmdate('Y-m-d H:i:s',time()+$days*86400)]);
            $this->w->audit('history.requested',$r['ref'],(int)$p['id']);$this->w->notify('history.requested',$r['ref'],(int)$p['user_id']);return $r;
        });
    }

    public function historyDecision(array $d): void
    {
        $this->w->transaction(function()use($d){
            $p=$this->w->ownPerson();
            $r=$this->w->db->query('SELECT * FROM cs_history_requests WHERE ref=? AND person_id=? FOR UPDATE',[$d['ref']??'',$p['id']])->getRowArray();
            if(!$r) throw new DomainError('Request unavailable.',404);
            $decision=$d['decision']??'';
            if($decision==='withdraw' && $r['state']==='withdrawn') return;
            if(strtotime($r['expires_at'].' UTC')<=time()) throw new DomainError('This authorization has expired.',409);
            if(($decision==='withdraw' && $r['state']!=='approved') || ($decision!=='withdraw' && $r['state']!=='requested')) throw new DomainError('This decision is no longer available.',409);
            if(!in_array($decision,['approve','decline','withdraw'],true)) throw new DomainError('Invalid decision.');
            $ids=[];
            if($decision==='approve') {
                if(($d['confirm']??'')!=='yes') throw new DomainError('Review the recipients, purpose, duration and exact entries before approving.');
                $refs=$d['entries']??[]; if(!is_array($refs) || !$refs) throw new DomainError('Select the career entries you permit.');
                $entries=$this->w->db->table('cs_career')->where('person_id',$p['id'])->whereIn('ref',$refs)->get()->getResultArray();
                if(count($entries)!==count(array_unique($refs))) throw new DomainError('One selected entry is unavailable.',404);
                $ids=array_map('intval',array_column($entries,'id'));
            }
            $state=['approve'=>'approved','decline'=>'declined','withdraw'=>'withdrawn'][$decision];
            $this->w->update('history_requests',$r,['state'=>$state,'entry_ids'=>json_encode($ids),'decision_at'=>gmdate('Y-m-d H:i:s')],(int)$d['version']);
            $this->w->audit('history.'.$state,$r['ref'],(int)$p['id']);
        });
    }

    public function readHistory(string $ref): array
    {
        return $this->w->transaction(function()use($ref){
            $this->w->require('history');$r=$this->w->record('history_requests',$ref,true);
            if($r['state']!=='approved' || strtotime($r['expires_at'].' UTC')<=time() || (int)$r['recipient_membership_id']!==(int)$this->w->membership['id']) throw new DomainError('This disclosure grant is unavailable or expired.',403);
            $ids=json_decode($r['entry_ids'],true);if(!$ids) return [];
            $entries=$this->w->db->table('cs_career')->select('ref,employer,role,start_period,end_period,precision_code,summary,source,disputed,created_at')->where('person_id',$r['person_id'])->whereIn('id',$ids)->get()->getResultArray();
            foreach($entries as &$entry){$old=$this->w->db->table('cs_career')->select('id')->where('ref',$entry['ref'])->get()->getRow();$entry['superseded']=$this->w->db->table('cs_career')->where('supersedes_id',$old->id)->countAllResults()>0;}
            $this->w->audit('history.viewed',$r['ref'],(int)$r['person_id'],'Approved career versions; named recipient only');return $entries;
        });
    }

    public function dispute(array $d): void
    {
        $this->w->transaction(function()use($d){
            $p=$this->w->ownPerson();$entry=$this->w->db->query('SELECT * FROM cs_career WHERE ref=? AND person_id=? FOR UPDATE',[$d['ref']??'',$p['id']])->getRowArray();
            if(!$entry) throw new DomainError('Career entry unavailable.',404);
            $r=$this->w->insert('disputes',['person_id'=>$p['id'],'career_id'=>$entry['id'],'reason'=>Workspace::text($d,'reason',3000)]);
            $this->w->update('career',$entry,['disputed'=>1]);$this->w->audit('dispute.opened',$r['ref'],(int)$p['id']);
        });
    }

    public function correctCareer(array $d):void
    {
        $this->w->transaction(function()use($d){
            $old=$this->w->db->query('SELECT * FROM cs_career WHERE ref=? FOR UPDATE',[$d['ref']??''])->getRowArray();
            if(!$old)throw new DomainError('Entry unavailable.',404);
            if($this->w->agency){$this->w->require('people');if((int)($old['source_agency_id']??0)!==(int)$this->w->agency['id'])throw new DomainError('Source entry unavailable.',404);}
            elseif((int)$old['person_id']!==(int)$this->w->ownPerson()['id']||$old['source_agency_id'])throw new DomainError('Source-confirmed facts use the dispute review route.',403);
            if($this->w->db->table('cs_career')->where('supersedes_id',$old['id'])->countAllResults())throw new DomainError('This version already has a correction.',409);
            $reason=Workspace::text($d,'reason',500);$new=$old;unset($new['id'],$new['ref'],$new['version'],$new['created_at'],$new['updated_at']);
            $new['summary']=Workspace::text($d,'summary',3000);$new['supersedes_id']=$old['id'];$new['source']=$old['source_agency_id']?'employer_corrected':'self_declared';$new['disputed']=0;
            $r=$this->w->insert('career',$new);$this->w->audit('career.corrected',$r['ref'],(int)$old['person_id'],$reason);$this->w->notify('career.correction_review',$old['ref']);
        });
    }
}
