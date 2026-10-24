<?php
namespace App\Commands;
use CodeIgniter\CLI\{BaseCommand,CLI};
use App\Services\{Workspace,OperationsService,DomainError};
class RaceWorker extends BaseCommand
{
    protected $group='CareSys tests';protected $name='caresys:test:race';protected $description='Isolated MySQL concurrency test worker; testing only.';
    public function run(array $params)
    {
        if(ENVIRONMENT!=='testing'||count($params)!==4){CLI::error('Testing-only worker (environment '.ENVIRONMENT.', arguments '.count($params).').');return EXIT_ERROR;}
        [$asset,$employee,$barrier,$worker]=$params;
        if(!preg_match('/^[a-f0-9]{24}$/D',$barrier)||!in_array($worker,['a','b'],true))return EXIT_ERROR;
        $db=db_connect('tests');$owner=$db->table('users')->where('username','owner')->get()->getRow();$agency=$db->table('cs_agencies')->where('name','Willow Care Agency')->get()->getRow();$w=new Workspace((int)$owner->id,$agency->ref,$db);
        $prefix=ROOTPATH.'build/qa/race-'.$barrier;file_put_contents($prefix.'-'.$worker.'.ready','ready');$limit=microtime(true)+15;
        while(!is_file($prefix.'.release')&&microtime(true)<$limit)usleep(10000);
        if(!is_file($prefix.'.release'))return EXIT_ERROR;
        try{(new OperationsService($w))->custody(['ref'=>$asset,'version'=>1,'decision'=>'assign','employment_ref'=>$employee,'condition_note'=>'Concurrency test '.$worker]);CLI::write('RACE_RESULT:assigned');}
        catch(DomainError $e){CLI::write('RACE_RESULT:denied:'.$e->status);}
        return EXIT_SUCCESS;
    }
}
