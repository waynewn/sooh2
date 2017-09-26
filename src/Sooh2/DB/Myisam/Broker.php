<?php
namespace Sooh2\DB\Myisam;



class Broker extends Cmd implements \Sooh2\DB\Interfaces\DBReal
{
    public function getConn() {
        return $this->connection;
    }
    protected $_tmpKvobjTable;
    public function kvobjTable($tb=null)
    {
        if($tb==null){
            return $this->_tmpKvobjTable;
        }else{
            return $this->_tmpKvobjTable=$tb;
        }
    }

    //public function createTable();
    
    public function getRecord($obj, $fields, $where=null, $sortgrpby=null)
    {
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'SELECT '.(is_array($fields)?implode(',', $fields):$fields)
                        .' from '.$this->fmtObj($obj, $this->connection->dbName)
                        .$this->buildWhere($where)
                        .$this->fmtSortGroup($sortgrpby)
                        .' limit 1';
        $rs0 = $this->exec(array($this->_lastCmd));
        $r = mysqli_fetch_assoc($rs0);
        
        mysqli_free_result($rs0);
        $this->skipErrorLog(null);
        return $r;
    }

    public function fetchResultAndFree($rsHandle)
    {
        $rs = array();
        while(null!==($r = mysqli_fetch_assoc($rsHandle))){
            $rs[]=$r;
        }
        mysqli_free_result($rsHandle);
        return $rs;
    }
    
    public function getOne($obj, $field, $where=null, $sortgrpby=null){
        $r = $this->getRecord($obj, $field, $where, $sortgrpby);
        if(sizeof($r)){
            return current($r);
        }else{
            return null;
        }
    }
    
    
    public function getRecords($obj, $fields, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'SELECT '.(is_array($fields)?implode(',', $fields):$fields)
        .' from '.$this->fmtObj($obj, $this->connection->dbName)
        .$this->buildWhere($where)
        .$this->fmtSortGroup($sortgrpby)
        .$this->fmtPage($rsFrom, $pageSize);
        $rs0 = $this->exec(array($this->_lastCmd));
        $rs = array();
        while(null!==($r = mysqli_fetch_assoc($rs0))){
            $rs[]=$r;
        }
        mysqli_free_result($rs0);
        $this->skipErrorLog(null);
        return $rs;
    }
    public function getCol($obj, $field, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'SELECT '.(is_array($field)?implode(',', $field):$field)
        .' from '.$this->fmtObj($obj, $this->connection->dbName)
        .$this->buildWhere($where)
        .$this->fmtSortGroup($sortgrpby)
        .$this->fmtPage($rsFrom, $pageSize);
        $rs0 = $this->exec(array($this->_lastCmd));
        $rs = array();
        while(null!==($r = mysqli_fetch_row($rs0))){
            $rs[]=$r[0];
        }
        mysqli_free_result($rs0);
        $this->skipErrorLog(null);
        return $rs;
    }
    public function getPair($obj, $fieldKey,$fieldVal, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'SELECT '.$fieldKey.','.$fieldVal
        .' from '.$this->fmtObj($obj, $this->connection->dbName)
        .$this->buildWhere($where)
        .$this->fmtSortGroup($sortgrpby)
        .$this->fmtPage($rsFrom, $pageSize);
        $rs0 = $this->exec(array($this->_lastCmd));
        $rs = array();
        while(null!==($r = mysqli_fetch_row($rs0))){
            $rs[$r[0]]=$r[1];
        }
        mysqli_free_result($rs0);
        $this->skipErrorLog(null);
        return $rs;
    }
    public function getRecordCount($obj, $where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'SELECT count(*)'
        .' from '.$this->fmtObj($obj, $this->connection->dbName)
        .$this->buildWhere($where);
        $rs0 = $this->exec(array($this->_lastCmd));
        $r = mysqli_fetch_row($rs0);
        mysqli_free_result($rs0);
        $this->skipErrorLog(null);
        return $r[0];
    }
    public function updRecords($obj,$fields,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'update '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '. $this->buildFieldsForUpdate($fields)
            .$this->buildWhere($where);
        
        $rs0 = $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        
        $affectedRows = mysqli_affected_rows($this->connection->connected);
        return $affectedRows>0?$affectedRows:true;
    }
    public function addRecord($obj,$fields,$pkey=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'INSERT into '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '.$this->buildFieldsForUpdate($fields,$pkey);

        $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        $insertId = mysqli_insert_id($this->connection->connected);
        
        return $insertId>0?$insertId:true;
    }
    public function delRecords($obj,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'delete from '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' '.$this->buildWhere($where);
        
        $rs0 = $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        
        $affectedRows = mysqli_affected_rows($this->connection->connected);
        return $affectedRows>0?$affectedRows:true;
    }
    
    
    public function ensureRecord($obj,$pkey,$fields,$fieldsWithValOnUpdate)
    {
        $this->_lastCmd = 'INSERT into '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '.$this->buildFieldsForUpdate($fields,$pkey).' ON DUPLICATE KEY UPDATE '.$this->buildFieldsForUpdate($fieldsWithValOnUpdate,$pkey);

        $this->exec(array($this->_lastCmd));
        
        return true;
    }
}

