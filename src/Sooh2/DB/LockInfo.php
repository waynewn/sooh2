<?php
namespace Sooh2\DB;
/**
 * 锁字段封装类（即使开启锁的时候的信息是空串，也没有扩展数据，串行化后也会需要大约50长度）
 * @author simon.wang
 *
 */
class LockInfo {
    private $constDtBase = 1483200000;//2017-1-1
    /**
     * 进程信息
     * @var string
     */
    public $processId=null;
    /**
     * 锁的原因
     * @var string
     */
    public $reason=null;
    /**
     * 锁定时间（时间戳）
     * @var int
     */
    public $time=null;
    /**
     * 锁定超时时间(时间戳)
     * @var int
     */
    public $expire=null;
    /**
     * 自定义扩展字段
     * @var string
     */
    public $ext=null;
    const status_locked = 'locked';
    const status_unlock = 'unlock';
    const status_expired= 'expired';
    /**
     * 当前状态（枚举值：locked,unlock,expired）
     * @var string
     */
    public $status = self::status_unlock;
    /**
     * 工厂方法：获取锁的实例
     * @param string|array $string 锁字段封装
     */
    public static function factory($string=null)
    {
        $o = new LockInfo();
        if(!empty($string)){
            $now = time();
            if(is_string($string)){
                $r = json_decode($string,true);
            }else{
                $r = $string;
            }
            $o->reason = $r['msg'];
            $o->processId = $r['pid'];
            $o->time = $r['dt'];
            $o->expire = $r['dt']+$r['dur'];
            $o->ext = $r['ext'];
            if($now>$o->expire){
                $o->status = self::status_expired;
            }else{
                $o->status = self::status_locked;
            }
        }
        return $o;
    }
    /**
     * 设置锁
     * @param string $reason 设置锁的事件描述
     * @param string $ext 扩展数据
     * @param int $dur 持续时间，默认1000天
     */
    public function lock($reason,$ext='',$dur=86400000)
    {
        if(class_exists('\\Sooh2\\Misc\\Ini',false)){
            $serverId=\Sooh2\Misc\Ini::getInstance()->getServerId();
        }else{
            $serverId = 0;
        }
        $this->processId = getmypid().'@'.$serverId;
        $this->reason=$reason;
        $this->time = time();
        $this->expire = $this->time+$dur;
        $this->ext = $ext;
        $this->status = self::status_locked;
        return $this;
    }

    /**
     * 字符串串型化
     */
    public function toString()
    {
        if($this->status==self::status_unlock){
            return '';
        }
        return \Sooh2\Util::toJsonSimple(array(
            'pid'=>$this->processId,
            'msg'=>$this->reason,
            'dt'=>$this->time,
            'dur'=>$this->expire-$this->time,
            'ext'=>$this->ext,
        ));
    }
}