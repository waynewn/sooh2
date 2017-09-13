<?php

/**
 * 网关转发
 */

namespace Sooh2\Misc;

/**
 * Description of ForwardCtrl
 *
 * @author simon.wang
 */
class ForwardCtrl {
    protected function ipWithPortlist(){
        return array(// 127.0.0.1:3456
        '127.0.0.1:9999',
        );
    }
    public function http()
    {
        $raw = file_get_contents('php://input');
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $curl= \Sooh2\Curl::factory();
        $uri = $_SERVER['DOCUMENT_URI'];
        $loger = \Sooh2\Misc\Loger::getInstance();
        $rs = $this->ipWithPortlist();
        $loger->app_trace("iurl[".$uri." ".json_encode($_GET)."]");
        $loger->app_trace("iurl[".$uri." ".json_encode($_GET)."]");
        $loger->app_trace("iurl[".$uri." ".json_encode($_GET)."]");
        $loger->app_trace("iurl[".$uri." ".json_encode($_GET)."]");
        $loger->app_trace("iurl[".$uri." ".json_encode($_GET)."]");
        foreach($rs as $ipWithPort){
            try{
                $url = $this->rewriteUri($ipWithPort, $uri);
                if(!empty($_GET)){
                    $url.= "?".http_build_query($_GET);
                }
                //$loger->app_trace("iurl[".$url." ".($raw. json_encode(array_keys($_POST)))."] --------------------------------------------------------");
                if(!empty($raw)){
                    $ret = $curl->httpPost($url, $raw);
                }elseif($method=='post'){
                    $ret = $curl->httpPost($url, $_POST);
                }else{
                    $ret = $curl->httpGet($url);
                }
                //$loger->app_trace("iurl[".$url." ".($raw. json_encode(array_keys($_POST)))."] --------------------------------------------------------【{$ret}】");
                if($this->ifNeedNext($ret)==false){
                    $loger->app_trace("iurl[".$url." ".($raw. json_encode($_POST))."] success? echo 【$ret】");
                    return;
                }
            } catch (\ErrorException $e){
                $loger->app_warning("iurl[".$url." ".($raw. json_encode($_POST))."] err".$e->getMessage());
            }
        }
        $loger->app_warning("iurl[".$url." ".($raw. json_encode($_POST))."] all failed");
    }
    /**
     * 根据情况替换接口地址（默认不换）
     * @param type $ipWithPort
     * @param type $uri
     * @return type
     */
    protected function rewriteUri($ipWithPort,$uri)
    {
        return 'http://'.$ipWithPort.'/'.$uri;
    }
    final protected function ifNeedNext($retOfCrul){
        $ret = $this->checkRet($retOfCrul);
        if(!empty($ret)){
            \Sooh2\Misc\ViewExt::getInstance()->initRenderType('echo');
            echo $ret;
            return false;
        }else{
            return true;
        }
    }
    /**
     * 如果是成功处理的，返回需要返回给外部调用者的信息，否则返回空串
     * @param type $retOfCrul
     * @return boolean
     */
    protected function checkRet($retOfCrul)
    {
        if($retOfCrul=='{"code":0,"message":"success"}'){
            return $retOfCrul;
        }else{
            return '';
        }
    }
}
