<?php
namespace Sooh2\DB\KVObj;

interface Interfaces
{
    public static function getCopy($pkey);
    /**
     * 获取存取记录所需要的db和tablename
     * @param int $splitIndex
     * @return array  [$db, $tbname]
     */
    public function dbAndTbName($splitIndex=null);
    /**
     * 获取记录数组
     */
    public function dump();
    /**
     * 获取主键
     */
    public function pkey();
    /**
     * 获取KVObjLoop工具类（遍历所有分表，找出符合条件的记录之类的需求）
     */
    public function getKVObjLoop();
    /**
     * 设定指定字段的值
     * @param string $k
     * @throws \ErrorException
     */
    public function setField($k,$v);
    /**
     * 指定字段的值加上一个数（负数）
     * @param string $k
     * @throws \ErrorException
     */
    public function incField($k,$v);
    /**
     * 获取指定字段的值
     * @param string $k
     * @throws \ErrorException
     */
    public function getField($k);
    /**
     * 存在判定（记录是否存在或指定field是否存在有效值）
     * @param string $fieldName 指定字段
     */
    public function exists($fieldName=null);
    /**
     * 加载数据库中的记录
     *  已加载过返回true，新加载返回pkey，找不到记录返回null
     *
     * @param bool $forceReload  是否强制从数据库读取一次
     * @see \Sooh2\DB\KVObj\KVObjBase::load()
     * @return mixed true on loaded already, pkey on first load ,null on no record
     */
    public function load($forceReload=false);
    
    /**
     * 设置成功加载后的回调函数
     * @param callback $func
     */
    public function setCallback_onload($func);
    /**
     * 释放指定的实例的资源，如果$obj=null,释放所有的
     * @param KVObj $obj
     * @return array 返回各类型剩余数量
     */
    public static function freeCopy($obj);
    /**
     * 释放占用的资源
     */
    public function free();
    /**
     * 锁记录（同时更新数据库）
     * @param string $reson 原因说明
     * @param string $ext   扩展数据
     * @param int $dur 锁多少秒（默认1000天）
     * @throws \ErrorException
     */
    public function lock($reson,$ext,$dur=86400000);
    /**
     * 解除记录锁（此时不更新数据库，需要手动调用saveToDB）
     * @throws \ErrorException
     */
    public function unlock();
    /**
     * 获取记录锁状态
     * @return string locked | expired | unlock
     */
    public function lockStatus();
    /**
     * 获取锁的信息（原因字段和扩展数据字段）
     * @return array [ lockReason, ext-data ]
     */
    public function lockObj();
    /**
     * 保存到数据库，可以尝试几次（保存失败后重新加载，调用预定义的操作函数，再次尝试保存），过程中碰到异常抛出不拦截
     * @param function $func_update 预定义的操作函数，第一次尝试时就调用了
     * @param int $maxRetry 重试次数(没设置$func_update的时候忽略此参数)
     * @throws \ErrorException  除了系统故障类报错以外，还可能因重试时条件不匹配丢出异常
     * @return bool 更新成功或失败
     */
    public function saveToDB($func_update=null, $maxRetry=3);
}

