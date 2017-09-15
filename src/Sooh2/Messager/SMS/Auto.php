<?php
namespace Sooh2\Messager\SMS;
/**
 * 轮询可用通道
 * 参数格式：senders=a,b,c
 * @author wangning
 */
class Auto extends \Sooh2\Messager\Sender{
    public function needsUserField() {   return 'phone';   }
    /**
     * 向指定（单个或一组）用户发消息
     * @param mixed $user 单用户用字符串，多个用户以数组方式提供
     * @param string $content 内容
     * @param string $title 标题，有些情况不需要
     * @throws \ErrorException
     * @return string 消息发送结果
     */
    public function sendTo($user,$content,$title=null)
    {
        if(!is_array($this->_ini['senders'])){
            $this->_ini['senders'] = explode(',', $this->_ini['senders']);
        }
        foreach($this->_ini['senders'] as $senderId){
            $sender = $this->getSenderById($senderId);
            try{
                return $sender->sendTo($user,$content,$title);
            } catch (\ErrorException $ex) {
                $this->onErrorFound($senderId, $ex->getMessage());
            }
        }
        throw new \ErrorException('all failed');
    }
    /**
     * @return \Sooh2\Messager\Broker
     */
    protected function getSenderById($senderId)
    {
        $conf = \Sooh2\Misc\Ini::getInstance()->getIni('Messager.'.trim($senderId));
        $class = $conf['class'];
        return $class::getInstance($conf['ini']);
    }
    
    protected function onErrorFound($senderId,$errmsg)
    {
        \Sooh2\Misc\Loger::getInstance()->app_warning('send sms  by '.$senderId.' failed ('.$errmsg.')');
    }
}
