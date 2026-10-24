<?php
namespace App\Controllers;
use App\Libraries\GeographyCatalog;
class ReferenceController extends BaseController
{
    public function locations()
    {
        $d=$this->request->getGet();$level=$d['level']??'countries';
        if(!is_string($level)||!in_array($level,['countries','states','counties','cities'],true))return $this->response->setStatusCode(422)->setJSON(['error'=>'Choose a valid location level.']);
        foreach(['country','state','county'] as $key)if(isset($d[$key])&&(!is_string($d[$key])||!preg_match('/^[A-Z0-9.-]{1,64}$/D',$d[$key])))return $this->response->setStatusCode(422)->setJSON(['error'=>'Invalid location selection.']);
        return $this->response->setHeader('Cache-Control','private, max-age=3600')->setJSON(['items'=>GeographyCatalog::items($level,$d['country']??'',$d['state']??'',$d['county']??'')]);
    }
}
