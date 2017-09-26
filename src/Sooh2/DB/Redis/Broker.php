<?php
namespace Sooh2\DB\Redis;
/**
 * 
 * @author simon.wang
 */
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

    protected function chkError($stepErrorId=null)
    {
        \Sooh2\Misc\Loger::getInstance()->sys_warning("TRACE chkerror in redis is ignored");
    }

    public function skipErrorLog($skipThisError)
    {
        \Sooh2\Misc\Loger::getInstance()->sys_warning("TRACE skipError in redis is ignored");
        return $this;
    }
    protected $skip=array();

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
                
                $chkkey = $this->exec( array( array('setnx', 'Sooh2lockkey:'.$k, 'u'.date('Y-m-d H:i:s')) ) );
                if($chkkey){
                    $ver = $this->getOne($k, $verK);
                    if($ver==$verV){
                        if($this->exec(array(array('hmset',$k,$fields)))){
                            $cmdBak = $this->_lastCmd;
                            $numOk++;
                        }
                    }
                    $this->exec( array( array('delete', 'Sooh2lockkey:'.$k) ) );
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

		$chkkey = $this->exec( array( array('setnx', 'Sooh2lockkey:'.$fullKey, 'a'.date('Y-m-d H:i:s')) ) );
		if($chkkey){
			$exists = $this->exec( array( array('exists', $fullKey) ) );
			if($exists){
				$err= new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::duplicateKey,'someelse are create same record',$this->lastCmd());
				$err->keyDuplicated = 'PRIMARY';
			}else{
				$this->exec( array( array('hmset', $fullKey, $fields) ) );
				$cmdBak = $this->_lastCmd;
			}
			$this->exec( array( array('delete', 'Sooh2lockkey:'.$fullKey) ) );
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

