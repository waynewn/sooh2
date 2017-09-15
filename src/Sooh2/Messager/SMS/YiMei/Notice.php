<?php
namespace Sooh2\Messager\SMS\YiMei;
/**
 * 亿美通知通道
 * 参数格式：cdkey=用户序列号&password=用户密码&signname=【xxxx】[&server=通道服务器地址]
 * @author simon.wang
 */
class Notice extends \Sooh2\Messager\Sender{
    public function needsUserField() {   return 'phone';   }
    protected function init($iniString){
        parent::init($iniString);
        if(empty($this->_ini['server'])){
            $this->_ini['server'] = 'http://hprpt2.eucp.b2m.cn:8080/sdkproxy/sendsms.action';
        }
        return $this;
    }
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
        $curl = \Sooh2\Curl::factory();
        $args = $this->_ini;
        $dt = str_replace('.','',substr(microtime(true).'000000',0,15));
        
        unset($args['signname']);unset($args['server']);

        $args['message']=$this->_ini['signname'].$content.'回复TD退订';
        $args['phone'] = is_array($user)?implode(',', $user):$user;
        //$args['addserial']= microtime(true);
        $args['seqid'] = $dt.sprintf('%02d',\Sooh2\Misc\Ini::getInstance()->getServerId()).rand(10,99);
        $args['smspriority']='1';
        //$url = $this->_url.'?'.http_build_query($args);
        //error_log( "\n\n".$url."\n");
        $ret = trim($curl->httpPost($this->_ini['server'],$args));
        if(strpos($ret,'<error>0</error>')){
            return $ret;
        }else{
            throw new \ErrorException($ret);
        }
    }
}

