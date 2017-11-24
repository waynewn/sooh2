<?php
/**
 *
 @example
 * Alicdn::getInstance($key,$secret,$act)->refresh($objectPath);  //刷新url
 * Alicdn::getInstance($key,$secret,$act)->refresh($objectPath,'Directory'); //刷新Directory
 */
namespace Sooh2\Cdn;

class Alicdn{
    protected $accessKeyId;
    protected $accessKeySecret;
    protected $activated;
    protected $errorMessage;
    protected static $_instance = array();

    public static function getInstance($initKey,$initSecret,$initAct){
        $className = get_called_class();
        if(!isset(self::$_instance[$className])){
            self::$_instance[$className] = new $className;
            self::$_instance[$className]->init($initKey,$initSecret,$initAct);
        }else{
            self::$_instance[$className]->init($initKey,$initSecret,$initAct);
        }
        return self::$_instance[$className];
    }


    protected function init($key,$secret,$act){
        $this->accessKeyId=$key;
        $this->accessKeySecret=$secret;
        $this->activated=$act;
    }




    /**
     * curl
     */
    protected function getRequest($curl, $https=true, $method='get', $data=null){
        $ch = curl_init();//初始化
        $headers = array("Content-type:application/json");
        curl_setopt($ch, CURLOPT_URL, $curl);//设置访问的URL
        curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers ); //设置请求头,json
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        if($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不做服务器认证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不做客户端认证
        }
        if($method == 'post'){
            curl_setopt($ch, CURLOPT_POST, true);//设置请求是POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置POST请求的数据

        }
        $str = curl_exec($ch);//执行访问，返回结果
        curl_close($ch);//关闭curl，释放资源
        return $str;
    }

    /**
     *设置公共参数
     * @return array
     */
    protected function setParameter(){
        date_default_timezone_set('UTC');
        $ymd=date("Y-m-d");
        $his=date("H:i:s");
        $timeStamp=$ymd."T".$his."Z";

        $signatureNonce = '';
        for($i =0 ; $i < 14; $i++){
            $signatureNonce .= mt_rand(0,9);
        }
        $publicParameter = array(
            'Format'         => 'JSON',
            'Version'        => '2014-11-11',
            'SignatureMethod'   => 'HMAC-SHA1',
            'TimeStamp'         => $timeStamp,
            'SignatureVersion'  => '1.0',
            'SignatureNonce'    => $signatureNonce,
            'AccessKeyId'       => $this->accessKeyId,
        );
        return $publicParameter;
    }

    /**
     * 拼接访问的url
     * @param array $parameter
     * @param string $accessKeySecret
     * @return string $url
     */
    protected function getStringToSign($parameter,$accessKeySecret){
        ksort($parameter);
        foreach($parameter as $key => $value){
            $str[] = rawurlencode($key). "=" .rawurlencode($value);
        }
        $ss = "";
        if(!empty($str)){
            for($i=0; $i<count($str); $i++){
                if(!isset($str[$i+1])){
                    $ss .= $str[$i];
                }
                else
                    $ss .= $str[$i]."&";
            }
        }
        $stringToSign = "GET" . "&" . rawurlencode("/") . "&" . rawurlencode($ss);


        $signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret."&", true));
        $url = "https://cdn.aliyuncs.com/?" . $ss . "&Signature=" . $signature;
        return $url;
    }


    public function getKeyId(){
        return $this->accessKeyId;
    }

    public function getKeySecret(){
        return $this->accessKeySecret;
    }

    public function getAct(){
        return $this->activated;
    }

    public function getErrorMessage(){
        return $this->errorMessage;
    }

    /**
     *刷新cdn缓存
     * @param string $objectPath
     * @param string $objectType
     * @param string $action
     * @return boolean
     */
    public function refresh($objectPath,$objectType="File",$action="RefreshObjectCaches"){
        if(!$this->activated){
            $data=array('Message'=>'没有激活刷新配置');
            $this->errorMessage=$data['Message'];
            return false;
        }
        for($i=0;$i<5;$i++) {
            //最多提交5次刷新，若不成功返回false
            $parameter=$this->setParameter();
            $parameter['ObjectPath']=$objectPath;
            $parameter['Action']=$action;
            $parameter['objectType']=$objectType;
            $url = $this->getStringToSign($parameter,$this->accessKeySecret);
            $data = $this->getRequest($url);
            $data = json_decode($data, true);
            if(isset($data['RefreshTaskId'])) {
                return $data;
            }else if($i==5){
                $this->errorMessage=$data['Message'];
                return false;
            }
        }
    }

}