<?php
namespace Sooh2;

class Util
{
    /**
     * 获取下一递增版本号
     * @param int $curId 当前值
     * @param int $i   1：加1   -1：减1
     */
    public static function autoIncCircled($curId,$i=1)
    {
        if($i>0){
            if($curId<99999999){
                return $curId+1;
            }else{
                return 1;
            }
        }else{
            if($curId==1){
                return 99999999;
            }else{
                return $curId-1;
            }
        }
    }

    /**
     * json_encode 的另一个实现，主要变更：
     *   1）数字不加引号
     *   2）字符串不编码，只将 " 替换为 \"
     * @param unknown $arr
     * @return string
     */
    public static function toJsonSimple($arr)
    {
        $ks = sizeof($arr);
        $isHash = false;
        for ($i=0;$i<$ks;$i++){
            if(!isset($arr[$i])){
                $isHash = true;
                break;
            }
        }
        $f  = __CLASS__."::".__FUNCTION__;
        if($isHash){
            $s = '{';
            foreach ($arr as $k=>$v){
                $s.='"'.$k.'":';
                if(is_array($v)){
                    $s.=$f($v).',';
                }else{
                    if(is_numeric($v)){
                        $s.=$v.',';
                    }else{
                        $s .= '"'.str_replace('"', '\\"', $v).'",';
                    }
                }
            }
            return substr($s,0,-1).'}';
        }else{
            $s = '[';
            foreach($arr as $v){
                if(is_array($v)){
                    $s.=$f($v).',';
                }else{
                    if(is_numeric($v)){
                        $s.=$v.',';
                    }else{
                        $s .= '"'.str_replace('"', '\\"', $v).'",';
                    }
                }
            }
            return substr($s,0,-1).']';
        }
    }
    /**
     * 注册onShutdown方法
     * @todo onShutdown
     */
    public static function onShutdown()
    {
        //if(class_exists('\Sooh2\DB\'))
    }
    /**
     * 获取指定两个字符串中间的字符串
     * @param string $content
     * @param string $begin
     * @param string $end
     * @return string
     */
    public static function getStrWithin($content,$begin,$end){
        $kl = strlen($begin);
        $pos = strpos($content, $begin);
        $poe = strpos($content, $end,$pos+$kl);
        $mid = substr($content,$pos+$kl,$poe-$pos-$kl);
        return $mid;
    }


    
    public static function remoteIP($proxyIP=null)
    {
        //$proxyIP = \Sooh\Base\Ini::getInstance()->get('inner_nat');
        if(!empty($proxyIP) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    public static function runBackground($cmd)
    {

        if(DIRECTORY_SEPARATOR =='/'){//unix
            $cmd =$cmd . " 2>&1 &";
        }else{//win
            $cmd ='start /b '.$cmd;
        }
        pclose(popen($cmd, 'r'));
        return $cmd;
    }
}

