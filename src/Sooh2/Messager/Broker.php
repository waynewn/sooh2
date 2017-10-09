<?php
namespace Sooh2\Messager;
/**
 * 根据定义的事件消息模板以及相关配置，完成多渠道发送，使用方法：
 * 1）派生Broker的子类，实现abstract方法（另外酌情改下记录操作日志的位置）
 * 2）调用方式：\Prj\EvtMsg\Sender::getInstance()->sendEvtMsg('BindOk', 'userId001', array('{bonus}'=>'xxxx元红包'));
 * @author simon.wang
 */
class Broker {
    protected static $_instance=null;
    /**
     * @return static
     */
    public static function getInstance($newInstance=null)
    {
        if($newInstance){
            self::$_instance = $newInstance;
        }elseif(self::$_instance===null){
            $c = get_called_class();
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
 
    /**
     * 根据事件消息模板id获取消息配置读取类
     * @param string $evtmsgid
     * @return \Sooh2\Messager\Interfaces\EvtMsgTpl
     */
    protected function getMsgTplCtrl($evtmsgid)
    {
        $obj = MsgTpl::getCopy($evtmsgid);
        $obj->load();
        return $obj;
    }
    
    /**
     * 根据发送器标示获取发送器
     * @param string $id
     * @return \Sooh2\Messager\Sender
     */
    protected function getSenderCtrl($id)
    {
        $conf = \Sooh2\Misc\Ini::getInstance()->getIni('Messager.'.$id);
        $class = $conf['class'];
        return $class::getInstance($conf['ini']);
    }

    /**
     * 获取用户对应发送渠道所需的字段（比如调用时用的uid，需要的是手机号）
     * @param mixed $user
     * @param \Sooh2\Messager\Sender $sender 
     * @return mixed 返回sender需要的用户的列表
     */
    protected function getUserForSender($user,$sender){
        return $user;
    }

    protected function onSendFailed($wayid,$evtmsgid,$userlist,$content,$reason)
    {
        \Sooh2\Misc\Loger::getInstance()->app_warning("EvtMSG $wayid [$evtmsgid] send $content to ". json_encode($userlist)." failed as: $reason");
    }
    protected function onSendSuccess($wayid,$evtmsgid,$userlist,$content,$ret)
    {
        \Sooh2\Misc\Loger::getInstance()->app_trace("EvtMSG $wayid [$evtmsgid] send $content to ". json_encode($userlist)." success with ret: $ret");
    }
    protected $_evtmsgid;
    public function sendEvtMsg($evtmsgid,$user,$replace)
    {
        $this->_evtmsgid = $evtmsgid;
        $msgTpl = $this->getMsgTplCtrl($evtmsgid);
        if($msgTpl){
            $title = str_replace(array_keys($replace),$replace,$msgTpl->getTitleTpl());
            $content = str_replace(array_keys($replace),$replace,$msgTpl->getContentTpl());
            $ways = $msgTpl->getWays();
            $this->sendCustomMsg($title, $content, $user, $ways,$evtmsgid,$replace);
        }else{
            throw new \ErrorException('msg config not found');
        }
    }
    
    public function sendCustomMsg($title,$content,$user,$ways,$evtmsgid='custom',$extarg=null)
    {
        foreach ($ways as $w){
            $sender = $this->getSenderCtrl($w);
            if($sender){
                $userlist = $this->getUserForSender($user, $sender);
                try{
                    $ret = $sender->sendTo($userlist, $content, $title,$extarg);
                    $this->onSendSuccess($w,$evtmsgid, $userlist, $content, $ret);
                }catch(\ErrorException $e){
                    $this->onSendFailed($w,$evtmsgid, $userlist, $content, $e->getMessage());
                }
            }else{
                $this->onSendFailed($w,$evtmsgid, $userlist, $content, 'msg-sender('.$w.') missing');
            }
        }
    }

}
