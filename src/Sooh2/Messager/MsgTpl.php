<?php

namespace Sooh2\Messager;

/**
 * 事件消息模板配置表
 * 
 * @author simon.wang
 */
class MsgTpl extends \Sooh2\DB\KVObj implements \Sooh2\Messager\Interfaces\EvtMsgTpl{
    /**
     * 
     * @param type $msgtplid
     * @return \Prj\Msg\MsgTpl
     */
    public static function getCopy($msgtplid) {
        return parent::getCopy(array('msgid'=>$msgtplid));
    }
    /**
     * 获取消息标题模板
     */
    public function getTitleTpl()
    {
        return $this->getField('titletpl');
    }
    /**
     * 获取消息内容模板
     */
    public function getContentTpl()
    {
        return $this->getField('contenttpl');
    }

    public function getWays()
    {
        return explode(',', $this->getField('ways'));
    }
}
