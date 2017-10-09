<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sooh2\Messager;

/**
 * Description of BrokerWithLog
 *
 * @author simon.wang
 */
abstract  class BrokerWithLog extends Broker{
    protected function onSendFailed($wayid,$evtmsgid,$userlist,$content,$reason)
    {
        $this->_loger->setResult($wayid, 'failed('.$reason.')@'.date('m-d H:i:s'));
        $logret = $this->_loger->saveToDB();
        
        \Sooh2\Misc\Loger::getInstance()->app_warning("EvtMSG $wayid [$evtmsgid] send $content to ". json_encode($userlist)." failed as: $reason (db-saved:". var_export($logret,true).")");
    }
    protected function onSendSuccess($wayid,$evtmsgid,$userlist,$content,$ret)
    {
        $this->_loger->setResult($wayid, 'success('.$ret.')@'.date('m-d H:i:s'));
        $logret=$this->_loger->saveToDB();
        \Sooh2\Misc\Loger::getInstance()->app_trace("EvtMSG $wayid [$evtmsgid] send $content to ". json_encode($userlist)." success with ret: $ret (db-saved:". var_export($logret,true).")");
    }
    /**
     *
     * @var \Sooh2\Messager\Interfaces\MsgSendLog
     */
    protected $_loger;
    protected function getLogerClassname()
    {
        return '\\Sooh2\\Messager\\MsgSentLog';
    }
    
    public function sendCustomMsg($title, $content, $user, $ways, $evtmsgid = 'custom',$extarg=null) {
        $logclass = $this->getLogerClassname();
        $this->_loger = $logclass::createNew($title,$content,$user,$ways,$evtmsgid,$extarg);
        if($this->_loger==null){
            \Sooh2\Misc\Loger::getInstance()->app_trace("EvtMSG[$evtmsgid] send $content to ". json_encode($user)." failed as : log record create failed");
            return;
        }
        $ret = parent::sendCustomMsg($title, $content, $user, $ways, $evtmsgid,$extarg);
        return $ret;
    }
    
    public function sendRetry($logid,$wayid){
        $logclass = $this->getLogerClassname();
        $this->_loger = $logclass::getCopy($logid);
        $this->_loger->load();
        if(!$this->_loger->exist()){
            \Sooh2\Misc\Loger::getInstance()->app_trace("EvtMSG[$evtmsgid] send $content to ". json_encode($user)." failed as : log record missing");
            return;
        }else{
            return parent::sendCustomMsg($this->_loger->getTitle(), $this->_loger->getContent(), $this->_loger->getUsers(), array($wayid), 'retry',$this->_loger->getExtargs());
        }   
    }
}
