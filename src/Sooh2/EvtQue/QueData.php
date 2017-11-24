<?php
namespace Sooh2\EvtQue;

/**
 * 使用mysql的一个table用作消息队列，从中获得一个任务数据
 * (暂不考虑分表以及redis等其它存储的情况,以及处理失败记录保留在任务表中的情况)
 * 并标记为有程序在处理，处理完后记录log
 * 数据库表结构
create table if not exists tb_evtque_0(
    evtid bigint not null auto_increment,
    evt varchar(36) not null default '',
    objid varchar(36) not null default '',
    uid varchar(36) not null default '',
    args varchar(36) default '',
    ret varchar(200) not null default '',
    rowVersion int not null default 0,
    primary key (evtid)
);
 * create table if not exists tb_evtque_log_0(...)
 * @author simon.wang
 */
class QueData extends \Sooh2\DB\KVObj{

    public static $pid;
    
    public static function addOne($evt,$objid,$uid,$args)
    {
        $db = parent::getCopy(null)->dbWithTablename();
        return $db->addRecord($db->kvobjTable(), array('evt'=>$evt.'','objid'=>$objid.'','uid'=>$uid.'','args'=>$args.'' , 'ret' => ''));
    }

    /**
     * 获得一个任务（指定或任意）
     * @param string $evt
     * @return \Sooh2\EvtQue\QueData
     */
    public static function getOne($evt=null)
    {
        $tmp = static::getCopy(null);
        list($db,$tb) = $tmp->dbAndTbName();
        $retry = 10;
        $trace = \Sooh2\Misc\Loger::getInstance();
        $bakLevel = $trace->traceLevel();
        $pid = getmypid().'@'.(\Sooh2\Misc\Ini::getInstance()->getServerId());
        self::$pid = $pid;
        while($retry){
            $retry --; //防止死循环
            $trace->traceLevel(0);
            if($evt==null){
                $r = $db->getRecord($tb,'*',array('ret'=>''));
            }else{
                $r = $db->getRecord($tb,'*',array('evtid'=>$evt,'ret'=>''));
            }
            $trace->traceLevel($bakLevel);
            if(empty($r)){
                return null;
            }
            
            $quedata = static::getCopy(array('evtid'=>$r['evtid']));
            try{
                unset($r['evtid']);
                foreach ($r as $k=>$v){
                    $quedata->setField($k, $v);
                }
                $quedata->setField('ret', 'by '.$pid.'@'.date('md-His'));
                
                $ret = $quedata->saveToDB();
                if($ret){
                    return $quedata;
                }//else更新失败的情况等下次重试，也许是被别人进程强去了
                
            } catch (\ErrorException $ex) {
                \Sooh2\Misc\Loger::getInstance()->app_warning('process quedata err:'.$ex->getMessage()."\n".$ex->getTraceAsString());
                static::freeCopy($quedata);
            }
        }
        return null;

    }
    /**
     * 获取事件数据
     * @return \Sooh2\EvtQue\EvtData
     */
    public function getEvtData()
    {
        $o = new EvtData();
        $o->evtId = $this->getField('evt');
        $o->objId = $this->getField('objid');
        $o->userId = $this->getField('uid');
        try{
            $o->args = $this->getField('args');
        }catch (\Exception $e){}
        return $o;
    }
    /**
     * 记录事件处理结果并清理资源
     * @param type $ret
     */
    public function endJob($ret){
        list($db,$tb) = $this->dbAndTbName();
        $this->setField('ret', $ret);
        if(false==\Sooh2\EvtQue\QueDataLog::createNew($this->_pkey['evtid'], $this->r)){
            \Sooh2\Misc\Loger::getInstance()->app_warning('事件('.$this->getEvtData()->toStringDetail().')处理结束:'.$ret.' 但记录日志失败');
        }
        $db->delRecords($tb,array('evtid'=>$this->_pkey['evtid']));
        static::freeCopy($this);
    }
    
    protected function onInit()
    {
        $this->className = 'EvtQue';
        
        parent::onInit();
        $this->field_locker=null;//  悲观锁用的字段名，默认使用'rowLock'，设置为null表明不需要悲观锁
        $this->_tbName = 'tb_evtque_{i}';//表名的默认模板
    }
}
