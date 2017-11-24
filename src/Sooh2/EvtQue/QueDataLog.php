<?php
namespace Sooh2\EvtQue;

/**
 * 事件队列处理结果日志
 *
 * @author simon.wang
 */
class QueDataLog extends \Sooh2\DB\KVObj{
    protected function onInit()
    {
        $this->className = 'EvtQueLog';
        parent::onInit();
        $this->field_locker=null;//  悲观锁用的字段名，默认使用'rowLock'，设置为null表明不需要悲观锁
        $this->_tbName = 'tb_evtque_log_{i}';//表名的默认模板
    }
    public static function createNew($evtId,$fields)
    {
        $tmp = static::getCopy(array('evtid'=>$evtId));
        if(isset($fields['evtid']))unset($fields['evtid']); //Hand 不写这句会报错
        if(isset($fields['rowVersion']))unset($fields['rowVersion']); //Hand 不写这句会报错
        foreach($fields as $k=>$v){
            $tmp->setField($k,$v);
        }
        try{
            $tmp->forceInsert();
            $ret = $tmp->saveToDB();
            if(!$ret){
                \Sooh2\Misc\Loger::getInstance()->app_warning("write quelog failed:(unknown) with ($evtId: ".\Sooh2\Util::toJsonSimple($fields).")");
            }else{
                return true;
            }
        }catch(\Sooh2\DB\DBErr $e){
            \Sooh2\Misc\Loger::getInstance()->app_warning('write quelog failed:'.$e->getMessage()." with ($evtId: ".\Sooh2\Util::toJsonSimple($fields).")");
        }

    }
}
