<?php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use App\Services\Workspace;

class DemoSeeder extends Seeder
{
    public const PASSWORD='CareSysDemo!2026';

    public function run(): void
    {
        if(!in_array(ENVIRONMENT,['development','testing'],true)) throw new \RuntimeException('Shared demo accounts are prohibited in production.');
        if($this->db->table('cs_agencies')->where('name','Willow Care Agency')->countAllResults()) {echo "Demo agencies already exist. Existing passwords and records preserved.\n";return;}
        $this->db->transException(true)->transBegin();
        try {
            $model=model(UserModel::class);
            $people=[];
            foreach([
                'platform'=>'Morgan Administrator','owner'=>'Avery Willow','recruiter'=>'Jordan Recruiter','scheduler'=>'Sam Scheduler','supervisor'=>'Taylor Supervisor','finance'=>'Casey Finance','caregiver'=>'Jamie Rivera','candidate'=>'Alex Chen','former'=>'Robin Ellis','unresolved'=>'Drew Morgan','owner2'=>'Harper Harbor','caregiver2'=>'Riley Brooks',
            ] as $key=>$name) {
                $email=$key.'@caresys.test';
                if($model->findByCredentials(['email'=>$email])) throw new \RuntimeException('Demo account already exists; refusing to reset its password.');
                $user=new User(['username'=>$key,'email'=>$email,'password'=>self::PASSWORD,'active'=>1]);
                if(!$model->save($user)) throw new \RuntimeException('Could not create a demo user.');
                $uid=$model->getInsertID();
                $p=$this->insert('cs_people',['user_id'=>$uid,'name'=>$name,'preferred_name'=>explode(' ',$name)[0],'identity_status'=>$key==='unresolved'?'review':'resolved','verified'=>$key==='unresolved'?0:1]);
                $people[$key]=$p;
                $this->insert('cs_profiles',['person_id'=>$p['id'],'role'=>'Caregiver','summary'=>$key==='candidate'?'Compassionate caregiver with five years of home support experience. Available for weekend and evening work.':'Experienced care professional committed to reliable, person-centered support.','skills'=>'Personal care, companionship, mobility support','qualifications'=>'First aid (self-declared)','work_type'=>'part_time','availability'=>'Weekends and weekday evenings','availability_at'=>gmdate('Y-m-d H:i:s'),'visibility'=>in_array($key,['candidate','former'],true)?'discoverable':'private','preferences'=>json_encode(['languages'=>'English, Spanish','travel_radius'=>'20 miles','care_settings'=>'Home care'])]);
                foreach(['US-CA-SAC','US-CA-YOLO'] as $region) $this->insert('cs_profile_regions',['person_id'=>$p['id'],'region'=>$region]);
            }
            $this->insert('cs_operators',['user_id'=>$people['platform']['user_id']]);
            $modules=array_values(array_unique(array_merge(...array_values(Workspace::ROLES))));$modules=array_values(array_diff($modules,['self','settings']));
            $agencies=[];
            foreach(['willow'=>'Willow Care Agency','harbor'=>'Harbor Home Support'] as $key=>$name) $agencies[$key]=$this->insert('cs_agencies',['name'=>$name,'verification'=>'verified','subscription'=>'active','modules'=>json_encode($modules),'agreement_basis'=>'Synthetic development entitlement; no payment collected']);
            foreach(['owner'=>'owner','recruiter'=>'recruiter','scheduler'=>'scheduler','supervisor'=>'supervisor','finance'=>'finance','caregiver'=>'caregiver'] as $key=>$role) $this->insert('cs_memberships',['agency_id'=>$agencies['willow']['id'],'user_id'=>$people[$key]['user_id'],'role'=>$role]);
            foreach(['owner2'=>'owner','caregiver2'=>'caregiver'] as $key=>$role) $this->insert('cs_memberships',['agency_id'=>$agencies['harbor']['id'],'user_id'=>$people[$key]['user_id'],'role'=>$role]);
            $employees=[];
            foreach(['caregiver'=>'willow','caregiver2'=>'harbor','former'=>'willow'] as $key=>$agency) {
                $employees[$key]=$this->insert('cs_employments',['agency_id'=>$agencies[$agency]['id'],'person_id'=>$people[$key]['id'],'employee_no'=>'DEMO-'.strtoupper($key),'role'=>'Caregiver','state'=>$key==='former'?'ended':'active','start_date'=>'2025-01-01','end_date'=>$key==='former'?'2026-09-30':null,'provider_ref'=>'DEMO-'.$key]);
                $this->insert('cs_leave_balances',['agency_id'=>$agencies[$agency]['id'],'employment_id'=>$employees[$key]['id'],'minutes'=>2400]);
            }
            foreach(['candidate','former','caregiver','caregiver2'] as $key) $this->insert('cs_career',['person_id'=>$people[$key]['id'],'source_agency_id'=>$key==='former'?$agencies['willow']['id']:null,'employer'=>$key==='former'?'Willow Care Agency':'Community Home Support','role'=>'Home care assistant','start_period'=>'2023-01','end_period'=>'2025-12','precision_code'=>'month','summary'=>'Companionship, mobility assistance and daily living support.','source'=>$key==='former'?'employer_confirmed':'self_declared']);
            foreach($agencies as $key=>$agency) {
                $vac=$this->insert('cs_vacancies',['agency_id'=>$agency['id'],'title'=>'Weekend Home Caregiver','region'=>'US-CA-SAC','work_type'=>'part_time','requirements'=>'Home care experience; valid first aid qualification','closing_date'=>gmdate('Y-m-d',time()+90*86400)]);
                if($key==='willow') $this->insert('cs_applications',['agency_id'=>$agency['id'],'vacancy_id'=>$vac['id'],'person_id'=>$people['candidate']['id'],'stage'=>'reviewing']);
                $client=$this->insert('cs_clients',['agency_id'=>$agency['id'],'name'=>$key==='willow'?'Pat Morgan (fictional)':'Lee Parker (fictional)','location'=>'Sacramento, CA — synthetic address','contact'=>'Authorized representative on file','instructions'=>'Support daily routines and mobility. Review the care plan before the visit. Synthetic demonstration record.']);
                $employee=$employees[$key==='willow'?'caregiver':'caregiver2'];
                $start=gmdate('Y-m-d').' 18:00:00';$end=gmdate('Y-m-d').' 21:00:00';
                $this->insert('cs_visits',['agency_id'=>$agency['id'],'client_id'=>$client['id'],'employment_id'=>$employee['id'],'starts_at'=>$start,'ends_at'=>$end,'service'=>'Home support']);
                $past=$this->insert('cs_visits',['agency_id'=>$agency['id'],'client_id'=>$client['id'],'employment_id'=>$employee['id'],'starts_at'=>gmdate('Y-m-d',time()-86400).' 18:00:00','ends_at'=>gmdate('Y-m-d',time()-86400).' 21:00:00','state'=>'completed','service'=>'Home support']);
                $this->insert('cs_timesheets',['agency_id'=>$agency['id'],'employment_id'=>$employee['id'],'visit_id'=>$past['id'],'seconds'=>10800,'state'=>'submitted']);
                $this->insert('cs_assets',['agency_id'=>$agency['id'],'tag'=>'KIT-001','name'=>'Care visit equipment bag']);
            }
            $this->insert('cs_audit',['actor_id'=>$people['platform']['user_id'],'action'=>'demo.seeded','detail'=>'Synthetic records and shared development accounts; never live verification or payment']);
            $this->db->transCommit();
            echo "Created 12 demo accounts, two isolated agencies and synthetic operational records.\n";
        }catch(\Throwable $e){$this->db->transRollback();throw $e;}
    }

    private function insert(string $table,array $data): array
    {
        $data['ref']=bin2hex(random_bytes(16));$this->db->table($table)->insert($data);$data['id']=$this->db->insertID();return $data;
    }
}
