<?php
namespace App\Services;

class ReviewService
{
    public function __construct(public Workspace $w){}

    public function qualification(array $d): void
    {
        $issued=Workspace::date($d['issued_on']??'');$expires=!empty($d['expires_on'])?Workspace::date($d['expires_on']):null;
        if($expires && $expires<$issued)throw new DomainError('Qualification expiry precedes issue date.');
        $r=$this->w->insert('qualifications',['person_id'=>$this->w->ownPerson()['id'],'name'=>Workspace::text($d,'name',160),'issuer'=>Workspace::text($d,'issuer',160),'issued_on'=>$issued,'expires_on'=>$expires,'evidence_ref'=>Workspace::text($d,'evidence_ref',200,false)]);$this->w->audit('qualification.declared',$r['ref'],(int)$this->w->person['id']);
    }

    public function acceptQualification(array $d): void
    {
        $this->w->transaction(function()use($d){
            $this->w->require('people');$e=$this->w->employment($d['employment_ref']??'');
            $q=$this->w->db->table('cs_qualifications')->where(['ref'=>$d['qualification_ref']??'','person_id'=>$e['person_id']])->get()->getRowArray();
            if(!$q)throw new DomainError('Qualification unavailable.',404);
            if($q['expires_on'] && $q['expires_on']<gmdate('Y-m-d'))throw new DomainError('An expired qualification cannot be accepted.');
            $r=$this->w->insert('qualification_acceptances',['agency_id'=>$e['agency_id'],'employment_id'=>$e['id'],'qualification_id'=>$q['id'],'reviewed_by'=>$this->w->userId,'evidence'=>Workspace::text($d,'evidence',500)]);$this->w->audit('qualification.agency_accepted',$r['ref'],(int)$e['person_id']);
        });
    }

    public function balance(array $d):void
    {
        $this->w->transaction(function()use($d){
            $this->w->require('leave');$e=$this->w->employment($d['employment_ref']??'');
            if((int)$e['person_id']===(int)$this->w->person['id'])throw new DomainError('Another reviewer must change your balance.',403);
            $minutes=filter_var($d['minutes']??null,FILTER_VALIDATE_INT);if($minutes===false||$minutes===0||abs($minutes)>525600)throw new DomainError('Enter a nonzero adjustment in minutes.');
            $b=$this->w->db->query('SELECT * FROM cs_leave_balances WHERE agency_id=? AND employment_id=? FOR UPDATE',[$e['agency_id'],$e['id']])->getRowArray();
            if(!$b || $b['minutes']+$minutes<0)throw new DomainError('The adjustment would produce a negative or unavailable balance.',409);
            $this->w->insert('balance_adjustments',['agency_id'=>$e['agency_id'],'employment_id'=>$e['id'],'actor_id'=>$this->w->userId,'minutes'=>$minutes,'reason'=>Workspace::text($d,'reason',500)]);$this->w->update('leave_balances',$b,['minutes'=>$b['minutes']+$minutes]);$this->w->audit('leave.balance_adjusted',$e['ref']);
        });
    }

    public function correction(array $d):void
    {
        $this->w->transaction(function()use($d){
            $this->w->require('self');$t=$this->w->record('timesheets',$d['ref'],true);$e=$this->w->table('employments')->where('id',$t['employment_id'])->get()->getRowArray();
            if((int)$e['person_id']!==(int)$this->w->ownPerson()['id'])throw new DomainError('Timesheet unavailable.',404);
            if($t['state']==='locked')throw new DomainError('This period is finalized. A provider adjustment is required.',409);
            $seconds=filter_var($d['proposed_seconds']??null,FILTER_VALIDATE_INT);if($seconds===false||$seconds<0||$seconds>172800)throw new DomainError('Check the proposed duration.');
            $r=$this->w->insert('time_corrections',['agency_id'=>$e['agency_id'],'timesheet_id'=>$t['id'],'requested_by'=>$this->w->userId,'proposed_seconds'=>$seconds,'source_version'=>$t['version'],'reason'=>Workspace::text($d,'reason',1000)]);$this->w->audit('time.correction_requested',$r['ref']);
        });
    }

    public function correctionDecision(array $d):void
    {
        $this->w->transaction(function()use($d){
            $this->w->require('time');$r=$this->w->record('time_corrections',$d['ref'],true);
            if((int)$r['requested_by']===$this->w->userId)throw new DomainError('You cannot approve your own correction.',403);
            if($r['state']!=='pending')throw new DomainError('Correction already reviewed.',409);
            $t=$this->w->table('timesheets')->where('id',$r['timesheet_id'])->get()->getRowArray();
            if($t['state']==='locked'||(int)$t['version']!==(int)$r['source_version'])throw new DomainError('The source changed or was finalized. Reconcile the correction.',409);
            $decision=$d['decision']??'';if(!in_array($decision,['approved','rejected'],true))throw new DomainError('Choose approve or reject.');
            if($decision==='approved')$this->w->update('timesheets',$t,['seconds'=>$r['proposed_seconds'],'state'=>'submitted','approved_by'=>null]);
            $this->w->update('time_corrections',$r,['state'=>$decision,'reviewed_by'=>$this->w->userId],(int)$d['version']);$this->w->audit('time.correction_'.$decision,$r['ref']);
        });
    }

    public function acknowledge(array $d):void
    {
        $this->w->transaction(function()use($d){$this->w->require('care');$r=$this->w->record('notes',$d['ref'],true);if($r['kind']!=='incident')throw new DomainError('Only incident records require acknowledgement.');$this->w->update('notes',$r,['state'=>'acknowledged'],(int)$d['version']);$this->w->audit('incident.acknowledged',$r['ref']);});
    }

    public function credit(array $d):void
    {
        $this->w->transaction(function()use($d){
            $this->w->require('finance');$i=$this->w->record('invoices',$d['ref'],true);
            if($i['state']==='credited'||$this->w->table('receipts')->where('invoice_id',$i['id'])->countAllResults())throw new DomainError('Paid or already credited invoices require a separate refund reconciliation.',409);
            $this->w->insert('credit_notes',['agency_id'=>$i['agency_id'],'invoice_id'=>$i['id'],'amount'=>$i['total'],'reason'=>Workspace::text($d,'reason',500)]);$this->w->update('invoices',$i,['state'=>'credited'],(int)$d['version']);$this->w->audit('invoice.credited',$i['ref']);
        });
    }
}
