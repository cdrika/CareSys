<?php
namespace App\Commands;

use CodeIgniter\CLI\{BaseCommand,CLI};
use CodeIgniter\Shield\{Entities\User,Models\UserModel};

class ProvisionWorkers extends BaseCommand
{
    protected $group='CareSys';
    protected $name='caresys:jobs:provision-local';
    protected $description='Create limited service principals for synthetic local agencies only.';

    public function run(array $params)
    {
        if(ENVIRONMENT!=='development'){CLI::error('Local service provisioning is development only.');return EXIT_ERROR;}
        $db=db_connect();$users=model(UserModel::class);$count=0;
        foreach($db->table('cs_agencies')->where('agreement_basis','Synthetic development entitlement; no payment collected')->get()->getResultArray() as $agency){
            if($db->table('cs_memberships')->where(['agency_id'=>$agency['id'],'role'=>'worker'])->countAllResults())continue;
            $db->transException(true)->transStart();$username='svc_'.substr($agency['ref'],0,20);
            $user=new User(['username'=>$username,'email'=>$username.'@caresys.invalid','password'=>bin2hex(random_bytes(48)),'active'=>1,'status'=>'service']);$users->save($user);$uid=$users->getInsertID();
            $db->table('cs_memberships')->insert(['ref'=>bin2hex(random_bytes(16)),'agency_id'=>$agency['id'],'user_id'=>$uid,'role'=>'worker','state'=>'active']);
            $db->table('cs_audit')->insert(['ref'=>bin2hex(random_bytes(16)),'agency_id'=>$agency['id'],'actor_id'=>$uid,'action'=>'worker.provisioned','detail'=>'Local synthetic agency activation authority; random private credential']);$db->transComplete();$count++;
        }
        CLI::write('Provisioned '.$count.' limited local service principals.');return EXIT_SUCCESS;
    }
}
