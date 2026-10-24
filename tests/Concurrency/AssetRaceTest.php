<?php
use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Workspace;

final class AssetRaceTest extends CIUnitTestCase
{
    public function testTwoIndependentMysqlTransactionsProduceOneCustodian():void
    {
        $runner=new \CodeIgniter\Database\MigrationRunner(config(\Config\Migrations::class),'tests');$runner->setNamespace(null);$runner->latest('tests');
        $db=db_connect('tests');$owner=$db->table('users')->where('username','owner')->get()->getRow();
        if(!$owner){ob_start();\Config\Database::seeder('tests')->call(\App\Database\Seeds\DemoSeeder::class);ob_end_clean();$owner=$db->table('users')->where('username','owner')->get()->getRow();}
        $agency=$db->table('cs_agencies')->where('name','Willow Care Agency')->get()->getRow();$w=new Workspace((int)$owner->id,$agency->ref,$db);
        $barrier=bin2hex(random_bytes(12));$prefix=ROOTPATH.'build/qa/race-'.$barrier;if(!is_dir(ROOTPATH.'build/qa'))mkdir(ROOTPATH.'build/qa',0770,true);
        $asset=$w->insert('assets',['agency_id'=>$agency->id,'tag'=>'RACE-'.substr($barrier,0,12),'name'=>'Concurrency fixture']);$employee=$w->table('employments')->where('state','active')->get()->getRowArray();
        $processes=[];$pipes=[];$env=getenv();$env['CI_ENVIRONMENT']='testing';
        try{
            foreach(['a','b'] as $worker){$descriptors=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$p=[];$processes[$worker]=proc_open([PHP_BINARY,ROOTPATH.'tests/Concurrency/worker.php','caresys:test:race',$asset['ref'],$employee['ref'],$barrier,$worker],$descriptors,$p,ROOTPATH,$env,['bypass_shell'=>true]);$pipes[$worker]=$p;fclose($p[0]);}
            $limit=microtime(true)+12;while((!is_file($prefix.'-a.ready')||!is_file($prefix.'-b.ready'))&&microtime(true)<$limit)usleep(20000);
            $this->assertFileExists($prefix.'-a.ready');$this->assertFileExists($prefix.'-b.ready');
            // Both independent processes are ready before either may begin its workflow.
            file_put_contents($prefix.'.release','release');$outputs=[];
            foreach(['a','b'] as $worker){$outputs[]=stream_get_contents($pipes[$worker][1]);stream_get_contents($pipes[$worker][2]);fclose($pipes[$worker][1]);fclose($pipes[$worker][2]);$this->assertSame(0,proc_close($processes[$worker]));unset($processes[$worker]);}
            $joined=implode("\n",$outputs);$this->assertSame(1,substr_count($joined,'RACE_RESULT:assigned'));$this->assertSame(1,substr_count($joined,'RACE_RESULT:denied:409'));
            $this->assertSame(1,$w->table('custody')->where('asset_id',$asset['id'])->where('returned_at',null)->countAllResults());
        }finally{foreach($processes as $process)if(is_resource($process))proc_terminate($process);}
    }
}
