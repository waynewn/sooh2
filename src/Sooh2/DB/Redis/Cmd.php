<?php
namespace Sooh2\DB\Redis;

class Cmd
{
    const selectDB = 'select';
    const setExpireTo='expireAt';
    const search_keys='keys';// use *
    const key_type = 'type';//string: set: list: zset: hash: other: 
    
    const get = 'get';
    const set = 'set';
    const set_expire = 'setex';
    const set_not_exists = 'setnx';
    const delete = 'delete';
    const exists = 'exists';
    const increase = 'incr';//needs set first
    const decrease = 'decr';
    const getMultiple = 'getMultiple';
    const array_unshift = 'lpush';
    const array_push = 'rpush';
    const array_shift = 'lpop';
    const array_size = 'llen';
    //const array_size = 'lsize';
    const set_add = 'sadd';
    const set_length = 'ssize';
    const set_exists = 'sContains';
    const set_remove = 'sRemove';
    
    const sortset_add = 'zAdd';  // key, worth, value
    const sortset_size = 'zSize';
    const sortset_count = 'zCount';// key,worth-start,worth-end
    const sortset_remore = 'zRem';
    const sortset_range = 'zRange';// get keys from start to end
    
    const hash_field_set = 'hSet';
    const hast_field_get = 'hGet';
    const hash_fields_count = 'hLen';
    const hash_fieldinc_step='hIncrBy';
    const hast_fields_all = 'hGetAll';
    /**
     * @var \Sooh2\DB\Interfaces\Conn
     */
    public $connection;
    public function connect()
    {
        if(!$this->connection->connected){
            try{
                \Sooh2\Misc\Loger::getInstance()->sys_trace("TRACE: Redis connecting");
                $this->connection->getConnHandle();
                $this->connection->change2DB($this->connection->dbNameDefault);
                return $this->connection->connected;
            }catch (\Exception $e){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage()." when try connect to {$this->connection->server} by {$this->connection->user}", "");
            }
        }
    }
    public function disconnect()
    {
        $this->connection->freeConnHandle();
    }    
    public function useDB($dbname)
    {
        try{
            if(!$this->connection->connected){
                $this->connect();
            }
            $this->connection->change2DB($dbname);
        }catch (\ErrorException $e){
            throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::dbNotExists, $e->getMessage(), "");
        }
    }
    public function buildWhere($define)
    {
        throw new \ErrorException('redis not support where');
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
    public function exec($cmds)
    {
        if(!$this->connection->connected){
            $this->connect();
        }
        $r = $this->_exec($cmds);
        if($this->tmpSwapDB!==null){
            $this->connection->change2DB($this->tmpSwapDB);
        }
        $this->tmpSwapDB=null;
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
                $this->tmpSwapDB = $this->connection->change2DB($r[0]);
            }
            $obj = $r[1];
        }
        
        $this->arrPkey=array();
        $this->arrVer=null;

        if($where===null){
            return array($obj);
        }elseif(is_array($where)){
            if(sizeof($where)==1){
                if(isset($where['|'])){
                    throw new \ErrorException('| not support in Redis.where');
                }elseif(isset($where['&'])){
                    $where = $where['&'];
                }
            }
            $v = \Sooh2\DB::version_field();
            $allkeys = array();
            if(isset($where[$v])){
                $this->arrVer=array('=',$v,$where[$v]);
                unset($where[$v]);
            }if(isset($where['='.$v])){
                $this->arrVer=array('=', $v, $where['='.$v]);
                unset($where[$v]);
            }
            
            $tmp = '';
            $rToBeReplace = null;
            $keyToBeReplace=null;
            $pkeyFormat = array();
            foreach ($where as $k=>$s){
                if(is_array($s)){
                    if(is_array($rToBeReplace)){
                        throw new \ErrorException("only one array support in where-part, given :".json_encode($where));
                    }
                    $rToBeReplace = $s;
                    $keyToBeReplace=$k;
                    $s = '{rToBeRepLace}';
                    $pkeyFormat[$k]='{rToBeRepLace}';
                }else{
                    $pkeyFormat[$k]=$s;
                }
                if(!is_numeric($k)){
                    $tmp .= ':'.$k.':'.$s;
                }else{
                    $tmp .= ':'.$s;
                }
            }

            if(is_array($rToBeReplace)){
                $pkeyFormat = json_encode($pkeyFormat);
                foreach ($rToBeReplace as $s){
                    $where[$keyToBeReplace]=$s;
                    $this->arrPkey[]=$where;
                    $allkeys[]=  $obj.str_replace('{rToBeRepLace}', $s, $tmp);
                }
            }else{
                $this->arrPkey[]=$where;
                $allkeys[]=  $obj.$tmp;
            }

            return $allkeys;
        }else{
            return array($obj.':'.$where);
        }

    }
    protected $arrPkey;
    protected $arrVer;
}