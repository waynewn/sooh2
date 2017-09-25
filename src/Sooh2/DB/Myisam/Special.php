<?php
namespace Sooh2\DB\Myisam;

/**
 * mysql 专属操作封装类
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
        if(!isset(\Sooh2\DB::$pool[$guid])){
            \Sooh2\DB::$pool[$guid] = new Special();
            \Sooh2\DB::$pool[$guid]->connection = $conn;
        }
        return \Sooh2\DB::$pool[$guid];
    }
    public function addLog($obj,$fields)
    {
        $this->connection->getConnection();
        $this->_lastCmd = 'INSERT DELAYED into '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '.$this->buildFieldsForUpdate($fields,null);
        $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        $insertId = mysqli_insert_id($this->connection->connected);
        return true;
    }
    
    public function resetAutoIncrement($obj,$newStartVal=1)
    {
        $this->_lastCmd= 'alter table '.$this->fmtObj($obj, $this->connection->dbName).' AUTO_INCREMENT='.$newStartVal;
        $this->exec(array($this->_lastCmd));
    }
}
