<?php
namespace Sooh2\DB\Mongodb;

/**
 * Special @ mongodb
 *
 * @author wangning
 */
class Special extends Broker{
        /**
     * 
     * @param array $arrConnIni
     * @return \Sooh2\DB\Mongodb\Special
     */
    public static function getInstance($arrConnIni)
    {
        $conn = \Sooh2\DB::getConn($arrConnIni);
       
        $guid = 'mongodb@'.$conn->guid;

        if(!isset(\Sooh2\DB::$pool[$guid])){
            \Sooh2\DB::$pool[$guid] = new Special();
            \Sooh2\DB::$pool[$guid]->connection = $conn;
        }
        return \Sooh2\DB::$pool[$guid];
    }
    public function getConFromDB(){throw new \ErrorException('todo');}
    public function findAndModify(){throw new \ErrorException('todo');}
    public function addIndex(){throw new \ErrorException('todo');}
    public function upsert(){throw new \ErrorException('todo');}
}
