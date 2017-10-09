<?php
namespace Sooh2\Messager\Email;
/**
 * smtp发送邮件
 * 需要配置格式：user=xxx&pass=xxxx&server=smtp.exmail.qq.com[&port=465]
 *
 * @author wangning
 */
class SmtpSSL extends \Sooh2\Messager\Sender{
    public function needsUserField() {   return 'email';   }
    protected function init($iniString)
    {
        parent::init($iniString);
        if(empty($this->_ini['port'])){
            $this->_ini['port']=465;
        }
        return $this;
    }
    /**
     * 向指定（单个或一组）用户发消息
     * @param mixed $user 单用户用字符串，多个用户以数组方式提供
     * @param string $content 内容
     * @param string $title 标题，有些情况不需要
     * @throws \ErrorException
     * @return string 消息发送结果,成功返回{"error":0}
     */
    public function sendTo($user,$content,$title=null,$args=null)
    {
        $mail = new Impl();
        //$mail->setServer("smtp@126.com", "XXXXX@126.com", "XXXXX"); //设置smtp服务器，普通连接方式
        $mail->setServer($this->_ini['server'], $this->_ini['user'], $this->_ini['pass'], 465, true); //设置smtp服务器，到服务器的SSL连接
        $mail->setFrom($this->_ini['user']); //设置发件人
        if(is_array($user)){
            foreach ($user as $u){
                $mail->setReceiver($u); //设置收件人，多个收件人，调用多次
            }
        }else{
            $mail->setReceiver($user);
        }
        //$mail->setCc("XXXX"); //设置抄送，多个抄送，调用多次
        //$mail->setBcc("XXXXX"); //设置秘密抄送，多个秘密抄送，调用多次
        //$mail->addAttachment("XXXX"); //添加附件，多个附件，调用多次
        $mail->setMail($title, $content); //设置邮件主题、内容
        $ret = $mail->sendMail(); //发送
        if($ret==false){
            throw new \ErrorException($ret);
        }else{
            return '{"error":0}';
        }
    }
}
