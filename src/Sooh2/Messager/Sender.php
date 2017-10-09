<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sooh2\Messager;

/**
 * 各种sender
 *
 * @author simon.wang
 */
class Sender {
    protected static $_instance = array();
    /**
     * @param string $msgCtrlClassName 类的名字
     * @param string $iniString 类的初始化参数,格式 var1=123&var2=245
     * @return \Sooh2\Messager\Sender
     */
    public static function getInstance($iniString)
    {
        $msgCtrlClassName = get_called_class();
        if(!isset(self::$_instance[$msgCtrlClassName])){
            self::$_instance[$msgCtrlClassName] = new $msgCtrlClassName;
            self::$_instance[$msgCtrlClassName]->init($iniString);
        }else{
            self::$_instance[$msgCtrlClassName]->init($iniString);
        }
        return self::$_instance[$msgCtrlClassName];
    }
    protected $_ini;
    
    protected function init($iniString)
    {
        parse_str($iniString,$this->_ini);
        return $this;
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
     * 向指定（单个或一组）用户发消息
     * @param mixed $user 单用户用字符串，多个用户以数组方式提供
     * @param string $content 内容
     * @param string $title 标题，有些情况不需要
     * @throws \ErrorException 出错或发送失败
     * @return string 发送成功，对方的返回结果
     */
    public function sendTo($user,$content,$title=null,$args=null)
    {
        throw new \ErrorException('todo');
    }
}
