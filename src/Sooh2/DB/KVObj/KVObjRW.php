<?php
namespace Sooh2\DB\KVObj;

/**
 * 读写分离的KVObj，读操作对应的类用KVObj即可，写操作的类用此类（比如mysql主从库）
 * 注意： getCopy的pkey 和 OnInit时设置读操作类
 */
class KVObjRW implements Interfaces
{
    protected static $_copies=array();
    public static function getCopy($pkey)
    {
        $c = get_called_class();
        $sn = http_build_query($pkey);
        if(!isset(self::$_copies[$c][$sn])){
            self::$_copies[$c][$sn] = new $c();
            self::$_copies[$c][$sn]->_pkey = $pkey;
            self::$_copies[$c][$sn]->onInit();
        }
        return self::$_copies[$c][$sn];
    }

    protected function onInit()
    {
//         $this->needTransData = true;
//         $this->_reader = KVObjBase::getCopy($this->_pkey);
//         $this->_writer = KVObjBase::getCopy($this->_pkey);

    }  
    
    protected $_pkey;
    /**
     * 写入用的kvobj
     * @var \Sooh2\DB\KVObj
     */
    protected $_writer=null;
    /**
     * 读取用的kvobj
     * @var \Sooh2\DB\KVObj
     */
    protected $_reader=null;    
    
    protected $isWriterLoaded=false;
    
    /**
     * 加载数据库中的记录
     *  已加载过返回true，新加载返回pkey，找不到记录返回null
     *
     * @param bool $forceReload  是否强制从数据库读取一次
     * @see \Sooh2\DB\KVObj\KVObjBase::load()
     * @return mixed true on loaded already, pkey on first load ,null on no record
     */
    public function load($forceReload=false){
        $this->_reader->load($forceReload);
        return $this;
    }
    /**
     * 从writer加载数据
     */
    protected function loadFromDisk()
    {
        $this->_writer->load(true);
        $this->isWriterLoaded=true;
        $r = $this->_writer->dump();
        if(is_array($r)){
            
            foreach($r as $k=>$v){
                if(in_array($k , array_keys($this->_reader->pkey())))continue; //防止主键被重复设置
                $this->_reader->setField($k, $v);
            }
            
            
        }
        return $this;
    }
    /**
     * 保存到数据库，可以尝试几次（保存失败后重新加载，调用预定义的操作函数，再次尝试保存），
     * 过程中碰到异常抛出不拦截，保存成功后，更新_reader数据（不落地）
     * @param function $func_update 预定义的操作函数，第一次尝试时就调用了
     * @param int $maxRetry 重试次数(没设置$func_update的时候忽略此参数)
     * @throws \ErrorException  除了系统故障类报错以外，还可能因重试时条件不匹配丢出异常
     * @return bool 更新成功或失败
     */
    public function saveToDB($func_update=null, $maxRetry=3){
        $verName = \Sooh2\DB::version_field();
        try{
            $verValue = $this->_writer->getField($verName);
            if($verValue != $this->_reader->getField($verName) && $func_update===null){//rowVersion not match
                return false;
            }
        }catch (\Exception $e){}

    
        $ret = $this->_writer->saveToDB($func_update,$maxRetry);
        if($ret){
            $r = $this->_writer->dump();
            foreach ($r as $k=>$v){
                $this->_reader->setField($k, $v);
            }
        }
        return $ret;
    }
    /**
     * 存在判定（记录是否存在或指定field是否存在有效值）
     * @param string $fieldName 指定字段
     */
    public function exists($fieldName=null){
        return $this->_reader->exists($fieldName);
    }    
    /**
     * 获取指定字段的值
     * @param string $k
     * @throws \ErrorException
     */
    public function getField($k){
        return $this->_reader->getField($k);
    }

    /**
     * 获取存取记录所需要的db和tablename
     * @param int $splitIndex
     * @return array  [$db, $tbname]
     */
    public function dbAndTbName($splitIndex=null,$readonly=false)
    {
        if($readonly){
            return $this->_reader->dbAndTbName($splitIndex,$readonly);
        }else{
            return $this->_writer->dbAndTbName($splitIndex,$readonly);
        }
    }
    /**
     * 获取存取记录所需要的db和tablename
     * @param int $splitIndex
     * @return array  [$db, $tbname]
     */
    public function dbWithTablename($splitIndex=null,$readonly=false)
    {
        if($readonly){
            return $this->_reader->dbWithTablename($splitIndex,$readonly);
        }else{
            return $this->_writer->dbWithTablename($splitIndex,$readonly);
        }
    }
    /**
     * 设置成功加载后的回调函数
     * @param callback $func
     */
    public function setCallback_onload($func){
        throw new \ErrorException('读写分离的情况下，不要使用此函数');
    }
    /**
     * 获取记录数组
     */
    public function dump(){
        return $this->_reader->dump();
    }
    /**
     * 获取主键
     */
    public function pkey(){
        return $this->_pkey;
    }
    /**
     * 获取KVObjLoop工具类（遍历所有写的分表，找出符合条件的记录之类的需求）
     */
    public function getKVObjLoop(){
        return $this->_writer->getKVObjLoop();
    }
    /**
     * 设定指定字段的值
     * @param string $k
     * @throws \ErrorException
     */
    public function setField($k,$v){
        if(!$this->isWriterLoaded){
            $this->loadFromDisk();
        }
        return $this->_writer->setField($k, $v);
    }
    /**
     * 指定字段的值加上一个数（负数）
     * @param string $k
     * @throws \ErrorException
     */
    public function incField($k,$v){
        if(!$this->isWriterLoaded){
            $this->loadFromDisk();
        }
        return $this->_writer->incField($k, $v);
    }



    /**
     * 锁记录（同时更新数据库）
     * @param string $reson 原因说明
     * @param string $ext   扩展数据
     * @param int $dur 锁多少秒（默认1000天）
     * @throws \ErrorException
     */
    public function lock($reson,$ext,$dur=86400000){
        if(!$this->isWriterLoaded){
            $this->loadFromDisk();
        }
        $ret = $this->_writer->lock($reson,$ext,$dur);
        if($ret){
            $this->_reader->lock($reson,$ext,$dur);
        }
        return $ret;
    }
    /**
     * 解除记录锁（此时不更新数据库，需要手动调用saveToDB）
     * @throws \ErrorException
     */
    public function unlock(){
        if(!$this->isWriterLoaded){
            $this->loadFromDisk();
        }
        $ret = $this->_writer->unlock();
        if($ret){
            $this->_reader->unlock();
        }
        return $ret;
    }
    /**
     * 获取记录锁状态
     * @return string locked | expired | unlock
     */
    public function lockStatus(){
        if(!$this->isWriterLoaded){
            $this->loadFromDisk();
        }
        return $this->_writer->lockStatus();
    }
    /**
     * 获取锁的信息（原因字段和扩展数据字段）
     * @return array [ lockReason, ext-data ]
     */
    public function lockObj($newObj=null){
        if($newObj===null){
            return $this->_reader->lockObj();
        }else{
            if(!$this->isWriterLoaded){
                $this->loadFromDisk();
            }
            $this->_writer->lockObj($newObj);
            $this->_reader->lockObj($newObj);
        }
        
    }

    /**
     * 释放占用的资源
     */
    public function free()
    {
        if($this->_writer){
            $this->_writer->free();
        }
        if($this->_reader){
        $this->_reader->free();
        }

        $this->_reader = null;
        $this->_writer = null;
    }
    /**
     * 释放指定的实例的资源，如果$obj=null,释放所有的
     * @param KVObj $obj
     * @return array 返回各类型剩余数量
     */
    public static function freeCopy($obj)
    {
        if($obj!=null){
            $c = get_class($obj);
            unset(self::$_copies[$c][$obj->objIdentifer]);
            $obj->free();
            $num = array();
            foreach(self::$_copies as $c=>$r){
                $num[$c] = sizeof($r);
            }
            return $num;
        }else{
            foreach(self::$_copies as $c=>$r){
                foreach ($r as $identifer=>$o){
                    unset(self::$_copies[$c][$identifer]);
                    $o->free();
                }
            }
            return array();
        }
    }
}

