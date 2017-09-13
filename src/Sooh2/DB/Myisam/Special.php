<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sooh2\DB\Myisam;

/**
 * Description of Special
 *
 * @author wangning
 */
class Special extends Cmd{
    /**
     * 
     * @param array $arrConnIni
     * @return \Sooh2\DB\Myisam\Special
     */
    public static function getInstance($arrConnIni)
    {
        $conn = \Sooh2\DB::getConn($arrConnIni);
        $guid = 'mysql@'.$conn->guid;
        if(isset(\Sooh2\DB::$pool[$guid])){
            \Sooh2\DB::$pool[$guid] = new Special();
            \Sooh2\DB::$pool[$guid]->connection = $conn;
        }
        return \Sooh2\DB::$pool[$guid];
    }
    public function addLog($obj,$fields)
    {
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'INSERT into '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '.$this->buildFieldsForUpdate($fields,null);

        $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        $insertId = mysqli_insert_id($this->connection->connected);
        
        return $insertId>0?$insertId:true;
    }
    
    public function resetAutoIncrement($obj,$newStartVal=1)
    {
        $this->_lastCmd= 'alter table '.$this->fmtObj($obj, $this->connection->dbName).' AUTO_INCREMENT='.$newStartVal;
        $this->exec(array($this->_lastCmd));
    }
}
