<?php
namespace Sooh2;
/**
 * send request by curl(cookies maintained)
 * 
 * @todo deal-with remove cookie
 * 
 * @author simon.wang
 *
 */
class Curl
{
    /**
     * 
     * @param array $cookie
     * @return Curl
     */
    public static function factory($cookie=array()){
        return new Curl($cookie);
    }
    public function __construct($cookies=array())
    {
        $this->cookies=$cookies;
    }
    public function initProxy($server,$port,$type='http',$user='',$pass='')
    {
        $this->proxy = array(
            'type'=>$type,
            'user'=>$user,
            'pass'=>$pass,
            'serv'=>$server,
            'port'=>$port,
        );
    }
    protected $proxy=null;
    public  $httpCodeLast=0;
    public  $cookies=null;
    public $headerReceived;
    public  function httpGet($url,$params=null,$arrHeaders=null ,$timeOut = 5)
    {
        if(!empty($params)){
            if(is_array($params)){
                $params = http_build_query($params);
            }
            if(strpos($url, '?')){
                $url.='&'.$params;
            }else{
                $url.='?'.$params;
            }
        }
        $ch = curl_init();
        if($ch){
            curl_setopt($ch, CURLOPT_URL, $url);
            if(is_array($arrHeaders) && !empty($arrHeaders)){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
            }
            
            $this->common_setting($ch,$timeOut);            
            $output = curl_exec($ch);
            $err=curl_error($ch);
            if(!empty($err)){
                error_log('[errorFailed:'.$err.']'.$url);
            }
            $this->httpCodeLast = curl_getinfo($ch,CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $output = $this->parseRet($output);
            
            return $output.$err;
        }else{
            return "Error:curl init failed";
        }
    }
    protected function common_setting($ch,$timeOut)
    {
        if(is_array($this->cookies) && !empty($this->cookies)){
            curl_setopt($ch, CURLOPT_COOKIE, str_replace('&', '; ', http_build_query($this->cookies)));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut ); //--tgh 160415
        if(is_array($this->proxy)){
            switch ($this->proxy['type']){
                case 'http':
                    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
                    //curl_setopt($ch, CURLOPT_PROXYTYPE,CURLPROXY_HTTP);//default is http
                    curl_setopt($ch, CURLOPT_PROXY,$this->proxy['serv'].':'.$this->proxy['port']);
                    ////curl_setopt($ch, CURLOPT_PROXYPORT, 8888);
                    if(!empty($this->proxy['pass'])){
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['user'].':'.$this->proxy['pass']);
//                         echo "proxy :".$this->proxy['serv'].':'.$this->proxy['port']." with pass used\n";
                    }else{
//                         echo "proxy :".$this->proxy['serv'].':'.$this->proxy['port']." used\n";
                    }
                    break;
                default:
                    throw new \ErrorException('proxy of curl not support yet');
            }
        }
        
        
    }
    protected function parseRet($output)
    {
        $posEnd = strpos($output, "HTTP/1.1 200");
        $posEnd = strpos($output, "\r\n\r\n",$posEnd);
        $this->headerReceived = substr($output, 0,$posEnd);
        
        
        preg_match_all('/^Set-Cookie: (.*?);/m',$this->headerReceived,$m);
        foreach($m[1] as $s){
            $posEq = strpos($s, '=');
            $this->cookies [ substr($s, 0,$posEq) ] = substr($s, $posEq+1);
        }
        return  substr($output, $posEnd+4);
    }
    public function httpPost($url,$params,$arrCookies=null,$arrHeaders=null,$timeOut=5)
    {
        if($arrHeaders!==null){
            if(!is_array($arrHeaders)){
                $arrHeaders[]=$arrHeaders;
            }
        }else{
            $arrHeaders = array();
        }
        if(is_string($params)){
            if($params==='{}' || $params==='[]'){
                $arrHeaders[] ='Content-Type: application/json';
                $arrHeaders[] ='Content-Length: 2';
            }else{
                $tmp = json_decode($params);
                if(!empty($tmp)){
                    // post raw-data of json
                    $arrHeaders[]='Content-Type: application/json';
                    $arrHeaders[] ='Content-Length: '.strlen($params);
                }else{
                    // post string
                }
            }
        }else{
            //post array
        }
        $ch = curl_init();

        if($ch){
            curl_setopt($ch, CURLOPT_URL, $url);

            if(is_array($params)){
                $tmp= http_build_query($params);
                curl_setopt($ch, CURLOPT_POST, 1);
                if(strlen($tmp)<1000){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $tmp);
                }else{
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }
            }else{
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
            if(sizeof($arrHeaders)){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
            }

            $this->common_setting($ch,$timeOut);            
            
            $output = curl_exec($ch);
            $err=curl_error($ch);
            $this->httpCodeLast = curl_getinfo($ch,CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $output = $this->parseRet($output);
            
            return $output.$err;
        }else{
            return "curl init failed";
        }
    }
    
    
}

