<?php
namespace Sooh2\DB\Myisam;



class Broker extends Cmd implements \Sooh2\DB\Interfaces\DBReal
{
    /**
     * 
     * @var \Sooh2\DB\Connections
     */
    public $connection;
    public function connect()
    {
        
        if(!$this->connection->connected){
            try{
                \Sooh2\Misc\Loger::getInstance()->sys_trace("TRACE: myisam connecting todo(usedb)");
                $this->connection->connected=mysqli_connect($this->connection->server,$this->connection->user,$this->connection->pass,null,$this->connection->port);
                if(!$this->connection->connected){
                    throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, mysqli_connect_errno().":".mysqli_connect_error(), "");
                }
                mysqli_select_db($this->connection->connected, $this->connection->dbNameDefault);
                $this->connection->dbName = $this->connection->dbNameDefault;
                if(!empty($this->connection->charset)){
                    $this->exec(array('set names '.$this->connection->charset));
                }
            }catch (\ErrorException $e){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage(), "");
            }
        }
    }
    public function useDB($dbname)
    {
        try{
            if(empty($dbname)){
                throw new \ErrorException('dbname not given');
            }
            if(!$this->connection->connected){
                $this->connect();
            }
            if($this->connection->dbName!=$dbname){
                mysqli_select_db($this->connection->connected, $dbname);
                $this->connection->dbName = $dbname;
            }
        }catch (\ErrorException $e){
            throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::dbNotExists, $e->getMessage(), "");
        }
    }
    protected function chkError($stepErrorId=null)
    {
        $errno = mysqli_errno($this->connection->connected);
        
        if ($errno) {
            $message = mysqli_error($this->connection->connected);
        
            switch ($errno){
                case 1054:$err=\Sooh2\DB\DBErr::fieldNotExists;break;
                case 1045:$err=\Sooh2\DB\DBErr::connectError;break;
                case 1049:$err=\Sooh2\DB\DBErr::connectError;break;
                	
                case 1050:$err=\Sooh2\DB\DBErr::tableExists;break;
                case 1146:$err=\Sooh2\DB\DBErr::tableNotExists;break;
                case 1060:$err=\Sooh2\DB\DBErr::fieldExists;break;
                case 1062:
                case 1022:
                case 1069:
                    //[1062]Duplicate entry '2' for key 'PRIMARY''
                    $dupKey = explode('for key ', $message);
                    $dupKey = trim(array_pop($dupKey),'\'');
                    $err=\Sooh2\DB\DBErr::duplicateKey;
                    break;
                default:$err=\Sooh2\DB\DBErr::otherError; break;
            }
            if(empty($this->skip) || !isset($this->skip[$err])){
                $ex=new \Sooh2\DB\DBErr($err,'['.$errno.']'.$message, $this->_lastCmd);
                if(!empty($dupKey)){
                    $ex->keyDuplicated=$dupKey;
                }
                \Sooh2\Misc\Loger::getInstance()->sys_warning("[".$ex->getCode()."]".$ex->getMessage()."\n". $this->_lastCmd."\n".$ex->getTraceAsString());
                throw $ex;
            }
        }        
        if(0!==($err=mysqli_errno($this->connection->connected))){
            throw new \Sooh2\DB\DBErr($stepErrorId?:\Sooh2\DB\DBErr::otherError, "[$err]".mysqli_error($this->connection->connected), $this->_lastCmd);
        }
    }

    public function skipError($skipThisError)
    {
        if($skipThisError===null){
            if(sizeof($this->skip)>0){
                $this->skip=array();
            }
        }else{
            $this->skip[$skipThisError]=$skipThisError;
        }
        return $this;
    }
    protected $skip=array();
    public function disconnect()
    {
        if($this->connection->connected){
            mysqli_close($this->connection->connected);
            $this->connection->connected=false;
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
        $this->skipError(null);
        return $r;
    }
    public function lastCmd()
    {
        return $this->_lastCmd;
    }
    protected $_lastCmd;
    public function exec($cmds)
    {
        if(!$this->connection->connected){
            $this->connect();
        }
        foreach($cmds as $cmd){
            \Sooh2\Misc\Loger::getInstance()->lib_trace("TRACE: try $cmd");
            $rs0 = mysqli_query($this->connection->connected, $this->_lastCmd=$cmd);
            $this->chkError();
        }
        return $rs0;
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
        $this->skipError(null);
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
        $this->skipError(null);
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
        $this->skipError(null);
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
        $this->skipError(null);
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
        $this->skipError(null);
        
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
        $this->skipError(null);
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
        $this->skipError(null);
        
        $affectedRows = mysqli_affected_rows($this->connection->connected);
        return $affectedRows>0?$affectedRows:true;
    }
    
    
    public function ensureRecord($obj,$pkey,$fields,$arrMethodFields){throw new \ErrorException('todo');}
}

