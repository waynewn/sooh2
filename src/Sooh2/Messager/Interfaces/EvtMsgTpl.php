<?php

namespace Sooh2\Messager\Interfaces;

/**
 * 用于获取消息模板的类
 *
 * @author simon.wang
 */
interface EvtMsgTpl {
    /**
     * 根据事件消息模板id获取 配置的实例
     * @param string $evtMsgId 事件消息模板id
     * @return \Sooh2\Messager\Interfaces\EvtMsgTpl
     */
    public static function getCopy($evtMsgId);
    /**
     * 获取消息标题模板
     */
    public function getTitleTpl();
    /**
     * 获取消息内容模板
     */
    public function getContentTpl();
    /**
     * 
     */
    public function getWays();
}
