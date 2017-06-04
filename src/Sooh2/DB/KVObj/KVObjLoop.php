<?php
namespace Sooh2\DB\KVObj;
class KVObjLoop
{
    protected static $_instances=array();
    /**
     * 获取KVObjLoop的实例
     * @param unknown $identifer
     * @param unknown $dbList
     * @return \Sooh2\DB\KVObj\KVObjLoop
     */
    public static function getInstance($identifer,$dbList)
    {
        if(!isset(self::$_instances[$identifer])){
            self::$_instances[$identifer] = new KVObjLoop();
            self::$_instances[$identifer]->list = $dbList;
        }
        return self::$_instances[$identifer];
    }
    protected $list;
    public function loop($func_db_tb)
    {
        foreach($this->list as $r){
            call_user_func($func_db_tb,$r[0],$r[1]);
        }
    }
}