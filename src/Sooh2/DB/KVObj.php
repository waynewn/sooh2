<?php
namespace Sooh2\DB;

use Sooh2\DB\KVObj\KVObjLoop;

class KVObj extends KVObj\KVObjBase
{
    /**
     * 初始化配置 , 每次派生记得根据情况修改这里设置的值
     * 配置文件里：  KVObj.xxxx = [numSplit,[db1_id,db2_id]], 类名xxx全部小写
     * 配置文件查找顺序  KVObj.classname => KVObj.default => 默认值 [1,array('defaulr')]
     */
    protected function onInit()
    {
        $this->field_locker=null;//  悲观锁用的字段名，默认使用'rowLock'，设置为null表明不需要悲观锁
        $this->indexes=array();//保留
        $objIni = \Sooh2\Misc\Ini::getInstance()->getIni('KVObj');
        if(isset($objIni[$this->className])){
            $objIni = $objIni[$this->className];
        }elseif(isset($objIni['default'])){
            $objIni = $objIni['default'];
        }else{
            $objIni = array('num'=>1, 'dbs'=>array('default'));
        }
        if(isset($objIni['num'])){
            $this->numSplit=$objIni['num']-0;
        }else{
            $this->numSplit=1;
        }
        
        $this->dbList = $objIni['dbs'];

        $this->objSplitIndex = $this->objSplitIndex%$this->numSplit;
        $this->_tbName = 'tb_'.strtolower($this->className).'_{i}';//表名的默认模板

    }
//     /**
//      * 针对主键是一个的情况使用取余的计算方式
//      * @param string $n
//      */
//     protected static function calcPkeyValOfNumber($n)
//     {
//         return array($n,$n%10000)
//     }

    
    /**
     * 获取指定主键对应的kvobj实例， 为了ide正确识别返回的类，这个函数建议每个派生类都写一下
     * @param array $pkey
     * @return static
     */
    public static function getCopy($pkey)
    {
        $c = get_called_class();
        if($pkey===null){
            $pkey = $objIdentifier = $objSplitIndex = 0;
        }else{
//            var_dump(self::calcPkeyVal($pkey));
            list($objIdentifier,$objSplitIndex) = self::calcPkeyVal($pkey);
        }
     
        if(!isset(self::$_copies[$c][$objIdentifier])){
            $o = new $c();
            $o->_pkey = $pkey;
            $tmp  =explode('\\',$c);
            $o->className = array_pop($tmp);
            $o->objIdentifer=$objIdentifier;
            $o->objSplitIndex=$objSplitIndex;
            $o->onInit();
            self::$_copies[$c][$objIdentifier]=$o;
        }
        if($objIdentifier===0){
            unset(self::$_copies[$c][$objIdentifier]);
            return $o;
        }else{
            return self::$_copies[$c][$objIdentifier];
        }
    }
    

    /**
     * 获取KVObjLoop工具类（遍历所有分表，找出符合条件的记录）
     * @return \Sooh2\DB\KVObj\KVObjLoop
     */
    public function getKVObjLoop()
    {
        $tmp = get_called_class();
        $tmp = $tmp::getCopy(null);
        $dblist = array();
        for($i=0;$i<$tmp->numSplit;$i++){
            $dblist[]=$tmp->dbAndTbName($i);
        }
        return KVObjLoop::getInstance(get_called_class(), $dblist);
    }

    /**
     * 设定指定字段的值
     * @param string $k
     * @throws \ErrorException
     */
    public function setField($k,$v)
    {
        $this->chged[$k]=$k;
        $this->r[$k]=$v;
    }
    /**
     * 指定字段的值加上一个数（负数）
     * @param string $k
     * @throws \ErrorException
     */
    public function incField($k,$v)
    {
        $this->chged[$k]=$k;
        $this->r[$k] +=$v;
    }    
    /**
     * 获取指定字段的值
     * @param string $k
     * @throws \ErrorException
     */
    public function getField($k,$enableNull=false)
    {
        if(isset($this->r[$k])){
            return $this->r[$k];
        }else{
            if($enableNull){
                return null;
            }else{
                throw new \ErrorException('try get field:'.$k.' in '.$this->className);
            }
        }
    }
    
    /**
     * 存在判定（记录是否存在或指定field是否存在有效值）
     * @param string $fieldName 指定字段
     */
    public function exists($fieldName=null)
    {
        if($fieldName===null){
            return !empty($this->r);
        }else{
            if(!empty($r) && !empty($r[$fieldName])){
                return true;
            }else {
                return false;
            }
        }
    }

    /**
     * 加载数据库中的记录
     *  已加载过返回true，新加载返回pkey，找不到记录返回null
     * 
     * @param bool $forceReload  是否强制从数据库读取一次
     * @see \Sooh2\DB\KVObj\KVObjBase::load()
     * @return mixed true on loaded already, pkey on first load ,null on no record
     */
    public function load($forceReload=false)
    {
        if($forceReload==true || empty($this->r)){
            $this->reload();
        }
        return $this;
    }
    
    /**
     * 设置成功加载后的回调函数
     * @param callback $func
     */
    public function setCallback_onload($func)
    {
        $this->callback_onload = $func;
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
            \Sooh2\DB\KVObj\KVObjRW::freeCopy(null);
            return array();
        }
    }
    /**
     * 释放占用的资源
     */
    public function free()
    {
        
    }
}
