<?php
namespace Sooh2\DB\Mongodb;

class Cmd
{
    const do_query='select';
    const do_insert='insert';
    const do_update='update';
    const do_delete='delete';
            
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
/**
     * 默认按 and 处理
     * @param unknown $define
     * @return string|unknown
     */
    public function buildWhere($define)
    {
        if(is_array($define)){
            $method = key($define);
            if(sizeof($define)!=1){
                 $method = '&';
                 return json_encode($this->parse($define));
            }else{
                switch ($method[0]){
                    case '&':
                        return json_encode($this->parse(current($define)));
                
                    case '|':
                        return json_encode($this->parse(current($define)));
                
                    default:
                        return json_encode($this->parse($define));
                }
            }

        }elseif(is_scalar($define)){
            if($define===''){
                return null;
            }else{
                return $define;
            }
        }
    }
    protected function parse($r)
    {
        if(!is_array($r)){
            throw new \ErrorException('invalid where-part found:'.json_encode($r));
        }
        $ret = array();
        foreach($r as $k=>$v){
            $k0 = $k[0];
            $field = substr($k,1);
            switch ($k0){
                case '&':
                    $ret = $this->parse($v);
                    break;
                case '|':
                    $ret['$or'] =$this->parse($v);
                    break;
                    
                case '<':
                    $ret[$field] = array('$lt'=>$v);
                    break;
                case '>':
                    $ret[$field] = array('$gt'=>$v);
                    break;
                case '[':
                    $ret[$field] = array('$lte'=>$v);
                    break;
                case ']':
                    $ret[$field] = array('$gte'=>$v);
                    break;
                case '*':
                    $ret[] = $field.' like '.$this->safeStr(str_replace('*', '%', $v));
                    break;
                case '!':
                    if(is_null($v)){
                        $ret[$field] = array('$nin'=>array(null),'$exists'=>true);
                    }elseif(is_array($v)){
                        $ret[$field] = array('$nin'=>$v);
                    }else{
                        $ret[$field] = array('$nin'=>array($v));
                    }
                    break;
                case '=':
                    if(is_null($v)){
                        $ret[$field] = array('$in'=>array(null),'$exists'=>true);
                    }elseif(is_array($v)){
                        $ret[$field] = array('$in'=>$v);
                    }else{
                        $ret[$field] = $v;
                    }
                default:
                    if(is_null($v)){
                        $ret[$k] = array('$in'=>array(null),'$exists'=>true);
                    }elseif(is_array($v)){
                        $ret[$k] = array('$in'=>$v);
                    }else{
                        $ret[$k] = $v;
                    }
                    break;
            }
        }
        return $ret;
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
        $r = $this->_exec($cmds);
        $lastRecordSet=null;
        foreach($cmds as $cmd){
            $f = array_shift($cmd);
            //dbname.tbname.insert({"_id":"abasdfhi3rabasdfhi3rabasdfhi3r123456","nickname":"张三","rowVersion":1});
            throw new \ErrorException('exec not support yet(mongodb)');
            $this->_lastCmd = $f.'('.json_encode($cmd).')';
            \Sooh2\Misc\Loger::getInstance()->lib_trace('TRACE: try '.$this->_lastCmd);
            if($f==self::do_query){
                $lastRecordSet = call_user_func_array(array($this->connection->connected,$f), $cmd);
            }else{
                $lastRecordSet = call_user_func_array(array($this->connection->connected,$f), $cmd);
            }
        }
        return $r;
    }
    
    protected function exec_read($db,$tb,$act='find',$where=null,$sort=null)
    {
        if(empty($where)){
            $filter = null;
        }else{
            $filter = $this->buildWhere($where);
        }
        $options = [
            'projection' => ['_id' => 0],
            'sort' => ['x' => -1],
        ];

        // 查询数据
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->connection->connected->executeQuery("$db.$tb", $query);
        
    }
    protected function exec_write($db,$tb,$act,$more)
    {
        
    }

    /**
     * 
     * @param string $obj
     * @return array(dbname,tbname)
     * @throws \ErrorException
     */
    protected function fmtObj($obj)
    {
        if(!is_string($obj) || empty($obj)){
            throw new \ErrorException("obj name should be string ".gettype($obj)." or empty-string given");
        }
        $r = explode('.', $obj);
        if(sizeof($r)==2){
            return $r;
        }else{
            return array($this->connection->dbName,$r[0]);
        }
    }

}