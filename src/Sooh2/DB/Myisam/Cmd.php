<?php
namespace Sooh2\DB\Myisam;

class Cmd
{
    /**
     * 
     * @var \Sooh2\DB\Interfaces\Conn
     */
    public $connection;
    public function connect()
    {
        
        if(!$this->connection->connected){
            try{
                $h = $this->connection->getConnHandle();
                if(!$h){
                    throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, mysqli_connect_errno().":".mysqli_connect_error(), "");
                }
                $this->connection->change2DB($this->connection->dbNameDefault);
                if(!empty($this->connection->charset)){
                    $this->exec(array('set names '.$this->connection->charset));
                }
            }catch (\ErrorException $e){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage()." when try connect to {$this->connection->server} by {$this->connection->user}", "");
            }
        }
    }
    public function useDB($dbname)
    {
        try{
            if(empty($dbname)){
                throw new \ErrorException('dbname not given');
            }
            $this->connection->getConnHandle();
            if($this->connection->dbName!=$dbname){
                $this->connection->change2DB($dbname);
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
            $ex=new \Sooh2\DB\DBErr($err,'['.$errno.']'.$message, $this->_lastCmd);
            if(!empty($dupKey)){
                $ex->keyDuplicated=$dupKey;
            }            
            if(!isset($this->skip[$err])){
                \Sooh2\Misc\Loger::getInstance()->sys_warning("[".$ex->getCode()."]".$ex->getMessage()."\n". $this->_lastCmd." by ".$this->connection->guid."\n".$ex->getTraceAsString());
            }
            $this->skip=array();
            throw $ex;
        }        
    }

    public function skipErrorLog($skipThisError)
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
        $this->connection->freeConnHandle();
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
    protected function fmtObj($obj, $defaultDB)
    {
        if(!is_string($obj)){
            throw new \ErrorException("obj name should be string ".gettype($obj)."given");
        }else{
            $r = explode('.', $obj);
            $l = sizeof($r);
            if($l===1){
                return $defaultDB.'.'.$obj;
            }elseif($l===2){
                return $obj;
            }else{
                throw new \ErrorException("invalid obj name given:".$obj);
            }
        }
        
    }
    
    protected function fmtPage($posFrom,$pageSize)
    {
        if($pageSize>0){
            if($posFrom>0){
                return ' limit '.$posFrom.','.$pageSize;
            }else{
                return ' limit '.$pageSize;
            }
        }
        return '';
    }
    
    protected function fmtSortGroup($sortgroup)
    {
        if($sortgroup===null){
            return '';
        }elseif(!is_string($sortgroup)){
            throw new \ErrorException("obj name should be string ".gettype($sortgroup)."given");
        }else{
            $arr = explode(' ',trim($sortgroup));
            $mx = sizeof($arr);
            $orderby=array();
            $groupby=array();
            for($i=0;$i<$mx;$i+=2){
                $k = $arr[$i];
                $v = $arr[$i+1];
                switch($k){
                    case 'rsort':
                        $orderby[]= $v.' desc';
                        break;
                    case 'sort':
                        $orderby[]= $v;
                        break;
                    case 'groupby':
                    case 'group':
                        $groupby[]= $v;
                        break;
                    default:
                        throw new \ErrorException('invalid sortgroup given:'.$sortgroup);
                }
            }
            if(!empty($groupby)){
                return ' group by '.implode (',', $groupby) . ( (!empty($orderby))?' order by '.implode (',', $orderby):'' );
            }else{
                if(!empty($orderby)){
                    return ' order by '.implode (',', $orderby);
                }else{
                    return '';
                }
            }
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
                 return ' WHERE '.implode(' AND ', $this->parse($define));
            }else{
                switch ($method[0]){
                    case '&':
                        return ' WHERE '.implode(' AND ', $this->parse(current($define)));
                
                    case '|':
                        return ' WHERE '.implode(' OR ', $this->parse(current($define)));
                
                    default:
                        return ' WHERE '.implode(' AND ', $this->parse($define));
                }
            }

        }elseif(is_scalar($define)){
            if($define===''){
                return '';
            }else{
                $cmp = substr($define,0,7);
                if(strtolower($cmp)===' WHERE '){
                    return $define;
                }else{
                    return ' WHERE '.$define;
                }
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
                    $ret[] = '('.implode(' AND ', $this->parse($v)).')';
                    break;
                case '|':
                    $ret[] = '('.implode(' OR ', $this->parse($v)).')';
                    break;
                    
                case '<':
                    $ret[] = $field.'<'.$this->safeStr($v);
                    break;
                case '>':
                    $ret[] = $field.'>'.$this->safeStr($v);
                    break;
                case '[':
                    $ret[] = $field.'<='.$this->safeStr($v);
                    break;
                case ']':
                    $ret[] = $field.'>='.$this->safeStr($v);
                    break;
                case '*':
                    $ret[] = $field.' like '.$this->safeStr(str_replace('*', '%', $v));
                    break;
                case '!':
                    if(is_null($v)){
                        $ret[] = $field.' is not null';
                    }elseif(is_array($v)){
                        $r = array();
                        foreach ($v as $s){
                            $r[] = $this->safeStr($s);
                        }
                        $ret[] = $field.' not in ('.implode(',', $r).')';
                    }else{
                        $ret[] = $field.'<>'.$this->safeStr($v);
                    }
                    break;
                case '=':
                    if(is_null($v)){
                        $ret[] = $field.' is null';
                    }elseif(is_array($v)){
                        $r = array();
                        foreach ($v as $s){
                            $r[] = $this->safeStr($s);
                        }
                        $ret[] = $field.'  in ('.implode(',', $r).')';
                    }else{
                        $ret[] = $field.'<>'.$this->safeStr($v);
                    }
                default:
                    if(is_null($v)){
                        $ret[] = $k.' is null';
                    }elseif(is_array($v)){
                        $r = array();
                        foreach ($v as $s){
                            $r[] = $this->safeStr($s);
                        }
                        $ret[] = $k.' in ('.implode(',', $r).')';
                    }else{
                        if(is_int($k) && is_string($v)){
                            $ret[] = $v;
                        }else{
                            $ret[] = $k.'='.$this->safeStr($v);
                        }
                    }
                    break;
            }
        }
        return $ret;
    }
    public function safeStr($s,$field=null,$obj=null)
    {
        if(is_scalar($s)){
            return '\''.addslashes($s).'\'';
        }else{
            return '\''.addslashes(json_encode($s)).'\'';
        }
        //mysqli_escape_string($link, $query)
    }
    //[a=>1, +b=2, .s='{s}a', 'a=a+bb' ]
    public function buildFieldsForUpdate($files1,$files2=null)
    {
        $tmp = array();
        if(is_array($files2)){
            foreach($files2 as $k=>$v){
                if(is_int($k)){
                    if(is_string($v)){
                        $tmp[]=$v;
                    }else{
                        $tmp[]=json_encode($v);
                    }
                }else{
                    $c = $k[0];
                    switch ($c){
                        case '+':
                        case '-':
                        case '*':
                        case '/':
                            $tmp[]=substr($k,1).'='.substr($k,1).$c.($v-0);
                            break;
                        case '.':
                            $tmp[]=substr($k,1).'='.substr($k,1).$c.($v-0);
                            break;
                        default:
                            if($v===null){
                                $tmp[]=$k.'=null';
                            }else{
                                $tmp[]=$k.'='.$this->safeStr($v);
                            }
                            break;
                    }
                    
                }
            }
        }
        foreach($files1 as $k=>$v){
                if(is_int($k)){
                    if(is_string($v)){
                        $tmp[]=$v;
                    }else{
                        $tmp[]=json_encode($v);
                    }
                }else{
                    if($v===null){
                        $tmp[]=$k.'=null';
                    }else{
                        $tmp[]=$k.'='.$this->safeStr($v);
                    }
                }
        }
        return implode(', ', $tmp);
    }
}

