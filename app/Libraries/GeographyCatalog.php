<?php
namespace App\Libraries;

class GeographyCatalog
{
    private static array $cache=[];
    public static function country(string $code):array
    {
        if(!preg_match('/^[A-Z]{2}$/D',$code))return [];
        if(!isset(self::$cache[$code])){
            $path=WRITEPATH.'reference/geography/'.$code.'.json';
            self::$cache[$code]=is_file($path)?json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR):[];
        }
        return self::$cache[$code];
    }
    public static function countries():array
    {
        $file=WRITEPATH.'reference/geography/countries.json';return is_file($file)?json_decode(file_get_contents($file),true):[];
    }
    public static function resolve(string $code):?array
    {
        // Preserve the original demo regions without asking users to understand their old codes.
        $code=['US-CA-SAC'=>'US-CA-067','US-CA-YOLO'=>'US-CA-113'][$code]??$code;
        $data=self::country(substr($code,0,2));$r=$data['lookup'][$code]??null;
        if(!$r)return null;
        $names=[$r['name']];
        foreach(['county','state','country'] as $level){$parent=$r[$level]??'';if($parent && $parent!==$code && isset($data['lookup'][$parent]))$names[]=$data['lookup'][$parent]['name'];}
        $r['label']=implode(', ',array_unique($names));return $r;
    }
    public static function items(string $level,string $country='',string $state='',string $county=''):array
    {
        if($level==='countries')return self::countries();
        $data=self::country($country);if(!$data)return [];
        if($state && (!isset($data['lookup'][$state])||$data['lookup'][$state]['level']!=='state'))return [];
        if($county && (!isset($data['lookup'][$county])||$data['lookup'][$county]['state']!==$state||$data['lookup'][$county]['level']!=='county'))return [];
        return match($level){'states'=>$data['states'],'counties'=>$data['counties'][$state]??[],'cities'=>array_values(array_filter($data['cities'][$state]??[],fn($r)=>!$county||$r['county']===$county)),default=>[]};
    }
    public static function label(string $codes):string
    {
        return implode('; ',array_map(fn($code)=>(self::resolve(trim($code))['label']??'Previously saved work area'),array_filter(explode(',',$codes))));
    }
}
