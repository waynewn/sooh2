<?php
namespace Sooh2\Messager\Push\Getui;

/**
 * 个推：针对所有设备的推送（忽略user参数）
 * 参数格式：appidkeys=id1,key1,MasterSecret1;id2,key2,MasterSecret2&localhost=本机标识&expire=超时秒数[&server=https://restapi.getui.com/v1/]
 * @author wangning
 */
class Basic2A extends \Sooh2\Messager\Sender{
    
    public function needsUserField() {   return 'pushid';   }
    protected function _log($ret,$url,$args)
    {
        \Sooh2\Misc\Loger::getInstance()->app_trace('[GeTui]'.$ret.' by '.$url. ' ' .(is_array($args)?\Sooh2\Util::toJsonSimple($args):$args));
    }

    protected function init($iniString)
    {
        parent::init($iniString);
        if(empty($this->_ini['server'])){
            $this->_ini['server']='https://restapi.getui.com/v1/';
        }
        return $this;
    }
    protected $tokenList = array();
    /**
     * 
     * @param \Sooh2\Curl $curl
     * @param type $appkey
     * @return type
     */
    protected function getToken($curl,$appid,$appkey,$mastersecret)
    {
        if(isset($this->tokenList[$appkey])){
            return $this->tokenList[$appkey];
        }
        $dt = time().'000';
        $sign = hash('sha256',$appkey.$dt.$mastersecret);
        $args = json_encode(array('sign'=>$sign,'timestamp'=>$dt,'appkey'=>$appkey));
        $ret = $curl->httpPost($this->_ini['server']."$appid/auth_sign", $args);
        $this->_log($ret, $this->_ini['server']."$appid/auth_sign", $args);
        $r = json_decode($ret,true);
        if(is_array($r)&&!empty($r['auth_token'])){
            return  $this->tokenList[$appkey]=$r['auth_token'];
        }else{
            return '';
        }
    }
    
    
    protected $lastReturnedTaskid='';
    public  function sendTo($user,$content,$title=null,$args=null)
    {
        //初始化所有的通道配置
        $curl = \Sooh2\Curl::factory();
        
        $idKey = array();
        $tmp= explode(';', $this->_ini['appidkeys']);
        foreach($tmp as $s){
            list($appid,$appkey,$mastersecret)=explode(',',$s);
            $idKey[$appid]['key']=$appkey;
            $idKey[$appid]['msc']=$mastersecret;
        }
        
        $nOK = 0;
        $nErr=array();

        foreach($idKey as $appid=>$appkey){
            //获取auth-token
            $params = $this->tplAll($content, $title, $args);
            $params['message']['appkey']=$appkey['key'];
            $authtoken =$this->getToken($curl,$appid, $appkey['key'],$appkey['msc']);
            if(empty($authtoken)){
                $nErr[]='get auth-token failed of '.$appid;
                continue;
            }
            
            $url = $this->_ini['server'].$appid.'/push_app';

            $ret = $curl->httpPost($url, $params,array('authtoken: '.$authtoken));
            $this->_log($ret, $url, $args);
            $tmp = json_decode($ret,true);
            //{"result":"ok", "cid_details":{"cid1":"no_user", "cid2":"successed_offline"}, "taskid":"RASL_0109_8b28bbad8a524e5799e8f07c8e79999"}
            //{"result":"ok","alias_details":{"130321051408G68I2gFstbFYamFpjpeb":{"3f48ccdb3c9637cf65b8ebfa898851fc":"successed_offline"}},"taskid":"RASL_0929_2989913b796644f3ba9eac17d80f0010"}
            if(is_array($tmp)&&$tmp['result']=='ok'){//{"result":"ok","taskid":"RASL_0119_5338f3c01e4f4a52bf08e26fa91da7e0"}
                $this->lastReturnedTaskid = $tmp['taskid'];
                return '';
            }else{
                return $ret;
            }
        }
        
        if($nOK==0){
            throw new \ErrorException('send request failed, last failed return='. array_pop($nErr)); 
        }else{
            return 'success sent request,lastReturnedTaskId='.$this->lastReturnedTaskid;
        }
    }
    protected static $autoInc=1;
    /**
     * 获取默认消息模板
     * @param type $content
     * @param type $title
     * @param type $args
     * @return type
     */
    protected function tplAll($content,$title=null,$args=null)
    {
        $arr= array(
                "message"=>array(
                   "appkey"=>'todo',
                   "is_offline"=>true,
                   "offline_expire_time"=>$this->_ini['expire']*1000,
                   "msgtype"=>"notification"
                ),

                "requestid"=>date('YmdHis').$this->_ini['localhost'].sprintf("%010d",self::$autoInc++),//请求唯一标识
        );

        $arr['notification']=array(

            'style'=>array(
                "type"=> 0,
                "text"=>$content,
                "title"=>$title,
//                "logo"=>"logo.png",
//                "logourl"=>"http://xxxx/a.png",
                "is_ring"=>true,
                "is_vibrate"=>true,
                "is_clearable"=>true
            ),
            'transmission_type'=>empty($args['pushdata'])?false:true,
            'transmission_content'=> empty($args['pushdata'])?'':$args['pushdata'],
//            'duration_begin'=>date('Y-m-d H:i:s',time()-60),
//            'duration_end'=>date('Y-m-d H:i:s',time()+$this->_ini['expire']),            
        );
        $arr['condition'] = array(
//            array("key"=>"phonetype", "values"=>"ANDROID", "opt_type"=>0),
//            array("key"=>"region", "values"=>array("11000000", "12000000"), "opt_type"=>0),
//            array("key"=>"tag", "values":["usertag"], "opt_type"=>0),
        );
        return $arr;

    }
    
    
}
