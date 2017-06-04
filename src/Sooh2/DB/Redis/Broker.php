<?php
namespace Sooh2\DB\Redis;
/**
 * @todo 还没想明白支持多个库的自动切换逻辑，始终在一个库没问题
 * @author simon.wang
 *
 */
class Broker extends Cmd implements \Sooh2\DB\Interfaces\DBReal
{
    /**
     * @var \Sooh2\DB\Connections
     */
    public $connection;
    public function connect()
    {
        if(!$this->connection->connected){
            try{
                \Sooh2\Misc\Loger::getInstance()->sys_trace("TRACE: Redis connecting");
                $this->connection->connected=new \Redis();
                
                $this->connection->connected->connect($this->connection->server, $this->connection->port);
                $this->connection->connected->auth($this->connection->pass);

                if(!$this->connection->connected){
                    throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, "connect to redis-server {$this->connection->server}:{$this->connection->port} failed", "");
                }
                $this->connection->dbName = $this->connection->dbNameDefault - 0;
                if($this->connection->dbName){
                    $this->connection->connected->select($this->connection->dbName);
                }
            }catch (\Exception $e){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage(), "");
            }
        }
    }
    public function useDB($dbname)
    {
        try{
            if(!$this->connection->connected){
                $this->connect();
            }
            $this->exec(array(array('select',$dbname)));
            $this->connection->dbName = $dbname;
        }catch (\ErrorException $e){
            throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::dbNotExists, $e->getMessage(), "");
        }
    }

    protected function chkError($stepErrorId=null)
    {
        \Sooh2\Misc\Loger::getInstance()->sys_warning("TRACE chkerror in redis is ignored");
    }

    public function skipError($skipThisError)
    {
        \Sooh2\Misc\Loger::getInstance()->sys_warning("TRACE skipError in redis is ignored");
        return $this;
    }
    protected $skip=array();
    public function disconnect()
    {
        if($this->connection->connected){
            $this->connection->connected->close();
            $this->connection->connected=false;
        }
    }
    //public function createTable();
    
    public function getRecord($obj, $fields, $where=null, $sortgrpby=null)
    {
        if(!$this->connection->connected){
            $this->connect();
        }
        $fullKey = $this->fmtObj($obj,$where);
        if(sizeof($fullKey)==1){
            $fullKey = current($fullKey);
        }else{
            throw new \ErrorException('only one pkey should be given');
        }

        $r = $this->getSpcFields($fullKey,$fields,$this->arrPkey[0]);
        return $r;
    }
    public function getOne($obj, $field, $where=null, $sortgrpby=null){
        $r = $this->getRecord($obj, '*', $where, $sortgrpby);
        if(is_array($r) && isset($r[$field])){
            return $r[$field];
        }else{
            return null;
        }
    }    
    /**
     * @todo 改造：根据情况选择 hgetall 还是 hmget
     * @param string $fullKey
     * @param string $fields
     * @throws \ErrorException
     */
    protected function getSpcFields($fullKey,$fields='*',$pkeyIgnore=array()){
        $r = $this->_exec(array(array('hGetAll',$fullKey)));
        if(!empty($this->arrVer)){
            if($this->arrVer[0]==='='){
                if($r[ $this->arrVer[1] ]!=$this->arrVer[2]){
                    return null;
                }
            }else{
                throw new \ErrorException('other method on ver-field is not support ');
            }
        }
        if($fields=='*'){
            return $r;
        }else{
            if(is_string($fields)){
                $fields = explode(',', $fields);
            }
            if(is_array($pkeyIgnore)){
                foreach ($pkeyIgnore as $k=>$v){
                    $r[$k]=$v;
                }
            }
            $ret = array();
            foreach ($fields as $k){
                $k = trim($k);
                $ret[$k]=$r[$k];
            }
            return $ret;
        }
    }
    public function getRecords($obj, $fields, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        if(!$this->connection->connected){
            $this->connect();
        }
        $fullKey = $this->fmtObj($obj,$where);
        $ret =array();
        foreach($fullKey as $i=>$k){
            $r = $this->getSpcFields($k,'*',$this->arrPkey[$i]);
            if($fields=='*'){
                $ret[] = $r; 
            }elseif(is_array($r)){
                $tmp =array();
                $ks = explode(',', $fields);
                foreach ($ks as $k){
                    $k=trim($k);
                    $tmp[$k]=$r[$k];
                }
                $ret[] = $tmp;
            }
        }
        
        return  $ret;
    }
       /**
        * @todo 锁定时最好记录时间和当时在干什么
        * {@inheritDoc}
        * @see \Sooh2\DB\Interfaces\DB::updRecords()
        */
    public function updRecords($obj,$fields,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }

        $fullKey = $this->fmtObj($obj,$where);
        $numOk = 0;
        if(!empty($this->arrVer)){
            $verCmp = $this->arrVer[0];
            $verK =$this->arrVer[1];
            $verV = $this->arrVer[2];

            foreach($fullKey as $k){
                
                $chkkey = $this->exec( array( array('setnx', 'lockkey:'.$k, 'u'.date('Y-m-d H:i:s')) ) );
                if($chkkey){
                    $ver = $this->getOne($k, $verK);
                    if($ver==$verV){
                        if($this->exec(array(array('hmset',$k,$fields)))){
                            $cmdBak = $this->_lastCmd;
                            $numOk++;
                        }
                    }
                    $this->exec( array( array('delete', 'lockkey:'.$k) ) );
                }else{
                    echo ">redis> lock failed: $k\n";
                }

            }
        }else{
            foreach($fullKey as $k){
                $exists = $this->exec( array( array('exists', $k) ) );
                if($exists){
                    if($this->exec(array(array('hmset',$k,$fields)))){
                        $cmdBak = $this->_lastCmd;
                        $numOk++;
                    }
                }
            }
        }
        if(!empty($cmdBak)){
            $this->_lastCmd = $cmdBak;
        }
        return ($numOk===0)?true:$numOk;
    }
    public function addRecord($obj,$fields,$pkey=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $fullKey = $this->fmtObj($obj,$pkey);
        if(sizeof($fullKey)==1){
            $fullKey = current($fullKey);
        }else{
            throw new \ErrorException('only one key should be returned');
        }

		$chkkey = $this->exec( array( array('setnx', 'lockkey:'.$fullKey, 'a'.date('Y-m-d H:i:s')) ) );
		if($chkkey){
			$exists = $this->exec( array( array('exists', $fullKey) ) );
			if($exists){
				$err= new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::duplicateKey,'someelse are create same record',$this->lastCmd());
				$err->keyDuplicated = 'PRIMARY';
			}else{
				$this->exec( array( array('hmset', $fullKey, $fields) ) );
				$cmdBak = $this->_lastCmd;
			}
			$this->exec( array( array('delete', 'lockkey:'.$fullKey) ) );
		}else{
			$err= new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::duplicateKey,'someelse are create same record',$this->lastCmd());
			$err->keyDuplicated = 'PRIMARY';
		}
		if(!empty($err)){
			$err->keyDuplicated='PRIMARY';
			\Sooh2\Misc\Loger::getInstance()->sys_warning("[".$err->getCode()."]".$err->getMessage()."\n". $this->_lastCmd."\n".$err->getTraceAsString());
			throw $err;
		}else{
		    $this->_lastCmd = $cmdBak;
			return true;
		}
        //array('hmset','tb_kv_0:k1:8:k2:88',array('v'=>888,'rowVersion'=>888)),
        
    }
    public function delRecords($obj,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $fullKey = $this->fmtObj($obj,$where);

        $r = $this->exec(array(array('delete',$fullKey)));
        if(empty($r)){
            return true;
        }else{
            return $r;
        }
    }
    public function exec($cmds)
    {
        if(!$this->connection->connected){
            $this->connect();
        }
        $r = $this->_exec($cmds);
        if($this->tmpSwapDB!=null){
            $this->_exec(array(array('select',$this->tmpSwapDB)));
            error_log("???????????????????????????".$this->connection->dbName . ' (tmp back to) '.$this->tmpSwapDB);
            $this->connection->dbName = $this->tmpSwapDB;
            $this->tmpSwapDB=null;
        }
        return $r;
    }
    protected $tmpSwapDB=null;
    protected function fmtObj($obj, $where)
    {

        if(!is_string($obj) || empty($obj)){
            throw new \ErrorException("obj name should be string ".gettype($obj)." or empty-string given");
        }
        $r = explode('.', $obj);
        if(sizeof($r)==2){
            if($this->connection->dbName!==$r[0]){
                error_log("???????????????????????????".$this->connection->dbName . ' (tmp set to) '.$r[0]);
                $this->tmpSwapDB = $this->connection->dbName;
                $this->_exec(array(array('select',$r[0])));
                $this->connection->dbName=$r[0];
            }
            $obj = $r[1];
        }
        return parent::fmtObj($obj, $where);
    }
    public function lastCmd()
    {
        return $this->_lastCmd;
    }
    protected $_lastCmd;
    protected function _exec($cmds){
        $lastRecordSet=null;
        foreach($cmds as $cmd){
            $f = array_shift($cmd);
            $this->_lastCmd = $f.'('.json_encode($cmd).')';
            \Sooh2\Misc\Loger::getInstance()->lib_trace('TRACE: try '.$this->_lastCmd);

            $lastRecordSet = call_user_func_array(array($this->connection->connected,$f), $cmd);
        }
        return $lastRecordSet;
    }

    public function fetchResultAndFree($rsHandle)
    {
        return $rsHandle;
    }

    public function getCol($obj, $field, $where=null, $sortgrpby=null,$rsFrom=0,$pageSize=null){
        throw new \ErrorException('todo');
    }
    public function getPair($obj, $fieldKey,$fieldVal, $where=null, $sortgrpby=null,$rsFrom=0,$pageSize=null){
        throw new \ErrorException('todo');
    }
    public function getRecordCount($obj, $where=null){
        throw new \ErrorException('todo');
    }
    
    
    
    public function ensureRecord($obj,$pkey,$fields,$arrMethodFields){throw new \ErrorException('todo');}
    public function safestr($str,$field=null,$obj=null){throw new \ErrorException('todo');}
}

