<?php
namespace Sooh2\Messager\SMS\Test;
/**
 * 测试用的短信通知通道
 * 配置格式是： 
 *     文件模式   type=file&path=xxxxx
 *     数据库模式 type=kvobj&class=xxxxx (todo)
 *     errorlog   type=errorlog
 *
 * @author simon.wang
 */
class Notice extends \Sooh2\Messager\Sender{
    protected function init($iniString)
    {
        parse_str($iniString,$this->_ini);
    }
    /**
     * 获取本类型消息需要的用户的哪个联系方式，目前支持 phone,email,innerid,outerid
     * @return string
     */
    public function needsUserField()
    {
        return 'phone';
    }
    /**
     * 
     * @param mixed $user 如果多个用户，以数组方式提供
     * @param string $content 内容
     * @param string $title 标题，有些情况不需要
     * @throws \ErrorException
     * @return string 消息发送结果
     */
    public function sendTo($user,$content,$title=null,$args=null)
    {
        error_log("sendEvtMsg 【 test-notice 】 ".json_encode($user).",$content,$title");
        switch ($this->_ini['type']){
            case 'file':file_put_contents($this->_ini['path'], '['.$user.']'.$content."\n",FILE_APPEND);break;
            case 'errorlog':error_log('['.$user.']'.$content);break;
            default:
                throw new \ErrorException('todo');
        }
        return 'done';
    }
}
