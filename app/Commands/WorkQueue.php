<?php
namespace App\Commands;

use CodeIgniter\CLI\{BaseCommand,CLI};
use App\Services\{Workspace,EmploymentService,DomainError};
use CodeIgniter\Shield\{Entities\User,Models\UserModel};

class WorkQueue extends BaseCommand
{
    protected $group='CareSys';
    protected $name='caresys:jobs:work';
    protected $description='Expire grants and activate due, fully authorized employments. Provider work stays pending until adapters are configured.';

    public function run(array $params)
    {
        $db=db_connect();$expired=0;$activated=0;$pending=0;
        $grants=$db->table('cs_history_requests')->whereIn('state',['requested','approved'])->where('expires_at <=',gmdate('Y-m-d H:i:s'))->limit(100)->get()->getResultArray();
        foreach($grants as $grant){
            $db->transBegin();$r=$db->query('SELECT * FROM cs_history_requests WHERE id=? FOR UPDATE',[$grant['id']])->getRowArray();
            if(in_array($r['state'],['requested','approved'],true)&&strtotime($r['expires_at'].' UTC')<=time()){
                $db->table('cs_history_requests')->where('id',$r['id'])->update(['state'=>'expired','version'=>$r['version']+1,'updated_at'=>gmdate('Y-m-d H:i:s')]);
                $db->table('cs_audit')->insert(['ref'=>bin2hex(random_bytes(16)),'agency_id'=>$r['agency_id'],'person_id'=>$r['person_id'],'action'=>'history.expired','record_ref'=>$r['ref'],'detail'=>'Expiry worker']);$expired++;
            }
            if($db->transStatus())$db->transCommit();else{$db->transRollback();CLI::error('Grant expiry failed.');return EXIT_ERROR;}
        }
        $due=$db->table('cs_joining j')->select('j.*,a.ref AS agency_ref')->join('cs_agencies a','a.id=j.agency_id')->where(['j.state'=>'authorized','j.conditions'=>'complete','a.verification'=>'verified'])->limit(100)->get()->getResultArray();
        foreach($due as $joining){
            $worker=$db->table('cs_memberships m')->select('m.user_id')->join('users u','u.id=m.user_id')->where(['m.agency_id'=>$joining['agency_id'],'m.role'=>'worker','m.state'=>'active','u.status'=>'service'])->get()->getRowArray();
            if(!$worker){$pending++;continue;}
            try{
                $w=new Workspace((int)$worker['user_id'],$joining['agency_ref']);
                (new EmploymentService($w))->activate(['ref'=>$joining['ref'],'version'=>$joining['version'],'request_key'=>'scheduled-'.$joining['ref']]);$activated++;
            }catch(DomainError){$pending++;}
        }
        CLI::write("Expired grants: {$expired}; activated employments: {$activated}; pending readiness review: {$pending}.");
        $provider=$db->table('cs_outbox')->where('state','pending_provider')->countAllResults();CLI::write("Provider work still pending configuration: {$provider}. No external messages or payments were submitted.");
        return EXIT_SUCCESS;
    }
}
