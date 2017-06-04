<?php
namespace Sooh2\Misc;

class ViewExt
{
    /**
     * 本次请求要求的输出格式
     * @var string
     */
    public static $renderType;
    public static $nameJsonP;
    /**
     * 留给框架，可以用于记录最后的输出串而不直接输出，保留最后处理的机会
     * @var string
     */
    public static $outputBuf;
    //需要设置headers的情况
    protected static $output = array();
    /**
     * 如果没拦截，返回空串，如果拦截了，设置header，返回输出内容
     */
    public static function renderInstead()
    {   
        if(empty(self::$output)){
            return '';
        }
        header(self::$output['head']);
        return self::$output['body'];
    }
    public static function onError($jsonError,$urlErrorPage)
    {
        if( self::$renderType==self::type_www || self::type_wap == self::$renderType){
            header('Location: '.$urlErrorPage);
        }elseif(self::$renderType==self::type_json){
            header('Content-type: application/json');
            echo $jsonError;
        }elseif(self::$renderType==self::type_jsonp){
            echo self::$nameJsonP.'('.$jsonError.')';
        }else{
            echo $jsonError;
        }
    }
    const viewRenderType = 'viewRenderType';
    const nameJsonP = 'nameJsonP';
    
    const type_www = 'www';
    const type_wap = 'wap';
    const type_json = 'json';
    const type_jsonp = 'jsonp';
    const type_echo = 'echo';
    const type_cmd = 'cmd';
    /**
     * 框架处理view的render时，拦截输出格式为json，echo，cmd等情况，返回true表示这里拦截处理了
     * @param array $arrTplVars
     * @return string 是否发生了拦截
     */
    public static function onFrameworkRender($arrTplVars)
    {
        $renderType = self::$renderType;
        
        if($renderType==self::type_json){
            self::$output['head']='Content-type: application/json';
            $ret = \Sooh2\Util::toJsonSimple($arrTplVars);
        }elseif($renderType==self::type_jsonp){
            $ret = \Sooh2\Util::toJsonSimple($arrTplVars);;
            $ret = self::$nameJsonP.'('.$ret.')';
        }elseif($renderType==self::type_cmd){
            $ret = '';
            foreach($arrTplVars as $k1=>$rs){
                $ret .= "$k1 :\n";
                foreach($rs as $k2=>$r){
                    if(is_array($r)){
                        $ret .= "\t$k2 :\n";
                        foreach($r as $k3=>$v){
                            $ret .= "\t\t$k3 :".(is_array($v)?\Sooh2\Util::toJsonSimple($v):$v)."\n";
                        }
                    }else{
                        $ret .= "\t$k2 : $r\n";
                    }
                }
            }
        }elseif($renderType==self::type_echo){
            $ret='';
        }else{
            return false;
        }
        self::$output['body'] = $ret;
        return true;
    }
    /**
     * 根据 www,wap 等细分替换 .phtml 为 www.phtml 和  .wap.phtml
     * @param string $strTpl
     * @throws \ErrorException
     * @return string
     */
    public static function fixTplFilename($strTpl)
    {
        switch (self::$renderType){
            case self::type_www:return str_replace('.phtml', '.www.phtml', $strTpl);
            case self::type_wap:return str_replace('.phtml', '.wap.phtml', $strTpl);
            case self::type_json:return;
            case self::type_jsonp:return;
            case self::type_echo:return;
            case self::type_cmd:return;
            default: throw new \ErrorException('render type not support:'.self::$renderType) ;
        }
    }
}

