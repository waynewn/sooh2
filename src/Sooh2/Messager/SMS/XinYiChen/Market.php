<?php
namespace Sooh2\Messager\SMS\XinYiChen;
/**
 * 欣易辰 营销短信通道(密码给md5)
 * 需要配置格式：loginName=xxx&password=xxxx&enterpriseID=企业ID[&server=通道服务器地址]
 * @author simon.wang
 */
class Market extends \Sooh2\Messager\Sender{
    public function needsUserField() {   return 'phone';   }
    protected function init($iniString){
        parent::init($iniString);
        if(empty($this->_ini['server'])){
            $this->_ini['server'] = 'http://113.108.68.228:8001/sendSMS.action';
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
    public function sendTo($user,$content,$title=null,$args=null)
    {
        $curl = \Sooh2\Curl::factory();
        $args = $this->_ini;
        unset($args['server']);
        $args['smsId']='';//消息id是这个包唯一标识，若为空则系统随机分配一个唯一标识值。若不为空，由用户自己设置一个唯一标识值。该值做为应答及状态报告中的消息id一一对应
        $args['subPort']='';
        $args['content']=$content;
        $args['mobiles'] = is_array($user)?implode(',', $user):$user;
        $args['sendTime']='';
        //$url = $this->_url.'?'.http_build_query($args);
        //error_log( "\n\n".$url."\n");
        $ret = trim($curl->httpPost($this->_ini['server'],$args));
        if(strpos($ret,'<Result>0</Result>')){
            return $ret;
        }else{
            throw new \ErrorException($ret);
        }
    }
}
