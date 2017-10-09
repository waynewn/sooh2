<?php
namespace Sooh2\Messager\Loger;
/**
 * log消息,需要的配置参数格式： file=/var/log/a.log
 *
 * @author simon.wang
 */
class Errorlog extends \Sooh2\Messager\Broker{
    public function sendTo($msg,$user=null,$args=null)
    {
        //file_put_contents($this->_ini['file'], '(to '.$user.')'.$msg."\n", FILE_APPEND);
        error_log("todo:messager:writelog:".$this->_ini['file'].": ".'(to '.$user.')'.$msg);
    }
}
