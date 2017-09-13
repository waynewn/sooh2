<?php
namespace Sooh2\DB\KVObj;
class KVObjBase
{
    /**
     * 主键，格式： [field1=>val1,field2=>val2]
     * @var array
     */
    protected $_pkey=null;
    /**
     * 分表值，用于计算到底分到哪张表
     * @var int
     */
    protected $objSplitIndex=0;
    /**
     * 总共分了几张表
     * @var int
     */
    protected $numSplit=1;
    /**
     * 分到了那几个数据库中
     * @var array
     */
    protected $dbList;
    /**
     * 数据库中的表的表名
     * @var string
     */
    protected $_tbName;
    /**
     * kvobj的实例的签名（用于找到记录的实例）
     * @var string
     */
    protected $objIdentifer='';
    /**
     * 派生类的类名
     * @var string
     */
    protected $className;
    /**
     * 记录的索引（保留，将来看处理一下，redis里想办法用）
     * @var unknown
     */
    protected $indexes;
    /**
     * 读取出的记录
     * @var array
     */
    protected $r=null;
    /**
     * 改过值的字段的列表
     * @var array
     */
    protected $chged=array();
    /**
     * 所有已加载的kvobj实例保存
     * @var unknown
     */
    protected static $_copies=array();
    /**
     * 记录锁字段名
     * @var string
     */
    protected $field_locker='rowLock';
    /**
     * 成功加载后的回调函数
     * @var callback
     */
    protected $callback_onload=null;
    protected $dbTbUsed=array();
    /**
     * 
     * @var \Sooh2\DB\LockInfo
     */
    protected $_lock=null;
    /**
     * 获取指定主键对应的db实例， 表名可通过db->kvobjTable()获得;
     * @param int $splitIndex 使用指定值计算而不是当前值
     * @return \Sooh2\DB\Myisam\Broker
     */
    public function dbWithTablename($splitIndex=null,$readonly=false)
    {
        list($db,$tb) = $this->dbAndTbName($splitIndex);
        $db->kvobjTable($tb);
        return $db;
    }
    /**
     * 获取指定主键对应的db实例 和 表名
     * @param int $splitIndex 使用指定值计算而不是当前值
     * @return array  [DB, tbname];
     */
    public function dbAndTbName($splitIndex=null,$readonly=false)
    {
        if($splitIndex===null){
            if(!empty($this->dbTbUsed)){
                return $this->dbTbUsed;
            }
            $chkid = $this->objSplitIndex;
        }else{
            $chkid = $splitIndex;
        }
        $dbsize = sizeof($this->dbList);
        $dbIniList = \Sooh2\Misc\Ini::getInstance()->getIni('DB');
        $r = explode('.', $this->dbList[$chkid%$dbsize]);
        if(sizeof($r)==1){//使用默认库
            $dbConf = $dbIniList[ $r[0] ];
            $tbName = $dbConf['dbName'];
        }else{//使用指定库
            $dbConf = $dbIniList[ $r[0] ];
            $tbName = $r[1];
        }
        $tbName .= '.'.str_replace('{i}', $chkid, $this->_tbName);
        if(empty($dbConf)){
            throw new \ErrorException('dbConf of kvobj:'.$this->className.' not found');
        }
        if($splitIndex==null){
            return $this->dbTbUsed = array(\Sooh2\DB::getDB($dbConf),$tbName);
        }else{
            return array(\Sooh2\DB::getDB($dbConf),$tbName);
        }
    }
    public function dump()
    {
        if(empty($this->r)){
            return null;
        }else{
            return array_merge($this->r,$this->_pkey);
        }
    }
    public function pkey()
    {
        return $this->_pkey;
    }
   /**
     * 根据pkey生成实例的签名, 和计算分表用的分表值（得出0-9999分布，如果是唯一的整数模式，直接使用该整数）
     * @param array $pkey
     * @return array [objIdentifier, objSplitIndex]
     */
    protected static function calcPkeyVal($pkey)
    {
        if(!is_array($pkey)){
            throw new \ErrorException('pkey should be array, '.var_export($pkey,true).' given');
        }
        if(sizeof($pkey)==1){
            return self::calcPkeyValOfOnefield(current($pkey));

        }else{
            $s0 = json_encode($pkey);
            $s = md5($s0);
            $n1 = base_convert(substr($s,-3), 16, 10);
            $n2 = base_convert(substr($s,-6,3), 16, 10);
            $n = $n2*100+($n1%100);
            return array($s0,$n%10000);
        }
    }
    /**
     * 针对主键是一个数字串的情况使用取余的计算方式，其它情况参看 calcPkeyVal()
     * @param string $n
     */
    protected static function calcPkeyValOfOnefield($n)
    {
        if(is_numeric($n) && !strpos($n, '.') && !strpos($n, 'e') && !strpos($n, 'E')){
            return array($n,substr($n,-4)-0);
        }else{
            $s = md5($n);
            $n1 = base_convert(substr($s,-3), 16, 10);
            $n2 = base_convert(substr($s,-6,3), 16, 10);
            $n = $n2*100+($n1%100);
            return array($n,$n%10000);
        }
    }


    /**
     * 加载记录
     * @throws \ErrorException
     * @return array pkey
     */
    protected function reload()
    {

        if(!empty($this->_pkey)){
            //deal with cache
            list($db,$tb) = $this->dbAndTbName();
           // error_log("kvobjloaded $tb : start 0");
            $this->r = $db->getRecord($tb, '*',$this->_pkey);
          //  error_log("kvobjloaded $tb :".\Sooh2\Util::toJsonSimple($this->r));
            if(!empty($this->r)){
                //try jsondecode
           //     error_log("kvobjloaded 1 :".\Sooh2\Util::toJsonSimple($this->r));
                if($this->field_locker!==null){
                    $this->_lock = \Sooh2\DB\LockInfo::factory($this->r[$this->field_locker]);
                    $this->r[$this->field_locker] = '--replace-by-kvobj--';
                }else{
                    $this->_lock = null;
                }
            //    error_log("kvobjloaded 2:".\Sooh2\Util::toJsonSimple($this->r));
                foreach($this->r as $k=>$v){
                    if(is_string($v)){
                        if(substr($v,0,1)==='{' && substr($v,-1)==='}'){
                            $this->r[$k] = json_decode($v,true);
                            if(empty($this->r[$k])){
                                $this->r[$k]=$v;
                            }
                        }elseif(substr($v,0,1)==='[' && substr($v,-1)===']'){
                            if($v=='[]'){
                                $this->r[$k] = array();
                            }else{
                                $this->r[$k] = json_decode($v,true);
                                if(empty($this->r[$k])){
                                    $this->r[$k]=$v;
                                }
                            }
                        }
                    }
                }
        
                $this->chged=array();
                
                if($this->callback_onload!==null){
                    call_user_func($this->callback_onload);
                }
                return $this->_pkey;
            }else{
                $this->r = array();
                return null;
            }
        }else{
            throw new \ErrorException('dont known what to load(pkey is '.json_encode($this->_pkey).')');
        }
        
    }
    protected function isLockedByMe()
    {
        
    }
    /**
     * 锁记录（同时更新数据库）
     * @param string $reson 原因说明
     * @param string $ext   扩展数据
     * @param int $dur 锁多少秒（默认1000天）
     * @throws \ErrorException
     */
    public function lock($reson,$ext,$dur=86400000)
    {
        if($this->field_locker===null || $this->_lock->status==\Sooh2\DB\LockInfo::status_locked){
            throw new \ErrorException('lock unsupport or locked already');
        }
        if(!empty($this->chged)){
            \Sooh2\Misc\Loger::getInstance()->app_trace('try lock '.$this->className.'('.\Sooh2\Util::toJsonSimple($this->pkey()).') with sth changed already');
        }
        $retry = 3;
        while($retry>0){
            $retry --;
            $this->_lock->lock($reson,$ext);
            $ret = $this->saveToDB();
            if($ret){
                return true;
            }
        }
        return false;
    }
    /**
     * 解除记录锁（此时不更新数据库，需要手动调用saveToDB）
     * @throws \ErrorException
     */
    public function unlock()
    {
        if($this->field_locker===null ){
            throw new \ErrorException('lock unsupport ');
        }
        $this->_lock->status=\Sooh2\DB\LockInfo::status_unlock;

    }
    /**
     * 获取记录锁状态
     * @return string locked | expired | unlock
     */
    public function lockStatus()
    {
        if($this->field_locker===null ){
            throw new \ErrorException('lock unsupport ');
        }
        return $this->_lock->status;
    }
    /**
     * 获取锁的实例（原因字段和扩展数据字段）
     * @return array [ lockReason, ext-data ]
     */
    public function lockObj($newOne=null)
    {
        if($newOne){
            $this->_lock = $newOne;
        }else{
            return $this->_lock;
        }
    }
    protected function verFieldName()
    {
        return \Sooh2\DB::version_field();
    }
    /**
     * 保存到数据库，可以尝试几次（保存失败后重新加载，调用预定义的操作函数，再次尝试保存），过程中碰到异常抛出不拦截
     * @param function $func_update 预定义的操作函数，第一次尝试时就调用了
     * @param int $maxRetry 重试次数(没设置$func_update的时候忽略此参数)
     * @throws \ErrorException  除了系统故障类报错以外，还可能因重试时条件不匹配丢出异常
     * @return bool 更新成功或失败
     */
    public function saveToDB($func_update=null, $maxRetry=3)
    {
        $loger = \Sooh2\Misc\Loger::getInstance();
        //error_log( "[tracelevel]".$loger->traceLevel());
        $verField = $this->verFieldName();
        $where = $this->_pkey;
        list($db,$tb) = $this->dbAndTbName();
        while($maxRetry){
            if($func_update===null){//没有处理任务，只尝试保存一次
                $maxRetry=1;
            }else{
                call_user_func($func_update,$this);//先执行处理任务
            }
            //准备更新
            $fields = array();
            foreach($this->chged as $k){
                $fields[$k]=$this->r[$k];
            }
            if($this->field_locker!==null){
                 if($this->_lock!==null){
                     $old=$this->r[$this->field_locker];
                     $this->r[$this->field_locker] = $this->_lock->toString();
                     if(!empty($old) && !empty($this->r[$this->field_locker])){
                         throw new \ErrorException('row is locked, check code,should check lock first');
                     }
                 }else{
                     $this->r[$this->field_locker]='';
                 }
                 $fields[$this->field_locker]=$this->r[$this->field_locker];
            }
            
            $verCur =$this->r[$verField];
            if($verCur>0 && $this->forceInsert==false){//已有记录更新
                $where[$verField] = $verCur;
                $fields[$verField]=\Sooh2\Util::autoIncCircled($verCur);
//                 error_log('upd - tb='.var_export($tb,true));
//                 error_log('upd - fields='.var_export($fields,true));
//                 error_log('upd - where ='.var_export($where,true));
                $ret = $db->updRecords($tb,$fields,$where);
                $loger->sys_trace("{$this->className}:[ret = ".var_export($ret,true)."] lastCmd".$db->lastCmd());
                if($ret===1){
                    $this->chged=array();
                    $this->r[$verField]=$fields[$verField];
                    return true;
                }else{
                    $loger->sys_trace("{$this->className}:".\Sooh2\Util::toJsonSimple($this->_pkey)." update failed, ver changed?");
                }
            }else{//新增记录
                if(empty($fields[$verField])){
                    $fields[$verField]=1;
                }
                try{
                    $ret = $db->addRecord($tb,$fields,$this->_pkey);
                    if($ret){
                        $this->chged=array();
                        $this->forceInsert=false;
                        $this->r[$verField]=$fields[$verField];
                        return true;
                    }else{
                        $loger->sys_trace("{$this->className}:".\Sooh2\Util::toJsonSimple($this->_pkey)." save new failed unknown");
                    }
                }catch (\Sooh2\DB\DBErr $e){
                    if(!empty($e->keyDuplicated)){
                        $loger->sys_trace("{$this->className}:".\Sooh2\Util::toJsonSimple($this->_pkey)." save failed as key-dup");
                        return false;
                    }
                }
            }
            //更新失败
            if($func_update===null){
//                $loger->sys_trace("{$this->className}:".\Sooh2\Util::toJsonSimple($this->_pkey)." save failed finally");
                return false;
            }
            $this->reload();
            
            $maxRetry --;
        }
        $loger->sys_trace("{$this->className}:".\Sooh2\Util::toJsonSimple($this->_pkey)." save failed all tried");
        return false;
    }
    protected $forceInsert=false;
    public function forceInsert()
    {
        $this->forceInsert=true;
    }
}