<?php
namespace Sooh2\Misc;

class ViewExt
{
    /**
     * 获取ViewExt实例
     * @param \Sooh2\Misc\ViewExt $newInstance 另行指定实例
     * @return \Sooh2\Misc\ViewExt
     */
    public static function getInstance($newInstance=null)
    {
        if(self::$_instance===null){
            if($newInstance===null){
                self::$_instance =  new ViewExt;
            }else{
                self::$_instance = $newInstance;
            }
        }
        return self::$_instance;
    }
    protected static $_instance = null;
    /**
     * 本次请求要求的输出格式
     * @var string
     */    
    protected $renderType;
    protected $nameJsonP;
    /**
     * 设置view格式
     * @param string $type  【json,www,wap,jsonp】
     * @param string $funcJsonP  jsonp格式时需要额外指定函数名
     * @return \Sooh2\Misc\ViewExt
     */    
    public function initRenderType($type,$funcJsonP=null){
        $this->renderType = $type;
        $this->nameJsonP = $funcJsonP;
        return $this;
    }
    protected $extTaskList=array();
    /**
     * 初始化查询状态的任务
     * @param array $classnameOfTask 需要处理的任务类
     * @return \Sooh2\Misc\ViewExt
     */ 
    public function initStatusTaskList($classnameOfTask)
    {
        if(is_array($classnameOfTask)){
            $this->extTaskList = array_merge($classnameOfTask, $this->extTaskList);
        }elseif(is_string($classnameOfTask)){
            $this->extTaskList[]=$classnameOfTask;
        }

        return $this;
    }
    /**
     * 追加查询状态的任务
     * @param type $taskid
     */
    public function appendStatusTask($taskid)
    {
        if(!in_array($taskid, $this->extTaskList)){
            $this->extTaskList[]=$taskid;
        }
    }
    
    protected $errPage='/404.html';
    /**
     * 设置默认的错误展示页面
     * @param string $errPageCommon
     * @return \Sooh2\Misc\ViewExt
     */
    public function initErrorPage($errPageCommon)
    {
        $this->errPage = $errPageCommon;
        return $this;
    }

    /**
     * 获取或设置输出字符串(留给框架，可以用于记录最后的输出串而不直接输出，保留最后处理的机会)
     * @param string $newval
     */
    public function outbuf($newval=null)
    {
        if($newval===null){
            return self::$output['body'];
        }else{
            self::$output['body'] .= $newval;
        }
    }
    
    //需要设置headers的情况
    protected static $output = array();
    /**
     * 如果没拦截，返回false，如果拦截了，设置header，返回输出内容
     * @return mixed 
     */
    public function renderInstead($isCli=false)
    {   
        if(empty(self::$output)){
            return false;
        }
        if($isCli==false && !empty(self::$output['head'])){
            header(self::$output['head']);
        }
        return self::$output['body'];
    }
    /**
     * 拦截的错误处理方式（直接输出了）
     * @param mixed $jsonError 错误信息，要么是ErrorException，要么建议是array(code=>404,'msg'=>'页面没找到')
     * @param string $urlErrorPage 
     */
    public function onError($jsonError)
    {
        if( $this->renderType==self::type_www || $this->renderType == self::type_wap){
            header('Location: '.$this->errPage);
        }elseif($this->renderType==self::type_json){
            header('Content-type: application/json');
            if(is_array($jsonError)){
                echo \Sooh2\Util::toJsonSimple($jsonError);
            }else{
                echo \Sooh2\Util::toJsonSimple(array('code'=>$jsonError->getCode(),'message'=>$jsonError->getMessage()));
            }
        }elseif($this->renderType==self::type_jsonp){
            if(is_array($jsonError)){
                echo $this->nameJsonP.'('.\Sooh2\Util::toJsonSimple($jsonError).')';
            }else{
                echo $this->nameJsonP.'('.\Sooh2\Util::toJsonSimple(array('code'=>$jsonError->getCode(),'message'=>$jsonError->getMessage())).')';
            }
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
     * 渲染view之前调用做最后的数据处理
     * @param \Sooh2\Yaf\Yaf_View_Simple $view
     */
    public function beforeRender($view)
    {
        if(!empty($this->extTaskList)){
            foreach($this->extTaskList as $c){
                if(class_exists($c)){
                    $o = new $c;
                    $o->appendData($this);
                }
            }
            $view->assign('extendInfo',$this->_status);
        }
    }
    public function _callForAddStatusData($k,$v)
    {
        $this->_status[$k]=$v;
    }
    protected $_status=array();
    /**
     * 框架处理view的render时，拦截输出格式为json，echo，cmd等情况，返回true表示这里拦截处理了
     * @param array $arrTplVars
     * @return bool 是否发生了拦截
     */
    public function onFrameworkRender($arrTplVars)
    {
        switch ($this->renderType){
            case self::type_json:
                self::$output['head']='Content-type: application/json';
                $ret = \Sooh2\Util::toJsonSimple($arrTplVars);
                break;
            case self::type_jsonp:
                $ret = \Sooh2\Util::toJsonSimple($arrTplVars);
                $ret = $this->nameJsonP.'('.$ret.')';
                break;
            case self::type_cmd:
                $ret = '';
                foreach($arrTplVars as $k1=>$rs){
                    if(!is_array($rs)){
                        $ret .="$k1 : $rs\n";
                        continue;
                    }else{
                        $ret .= "$k1 :\n";
                    }
                    foreach($rs as $k2=>$r){
                        if(!is_array($r)){
                            $ret .= "\t$k2 : $r\n";
                            continue;
                        }
                        $ret .= "\t$k2 :\n";
                        foreach($r as $k3=>$v){
                            $ret .= "\t\t$k3 :".(is_array($v)?\Sooh2\Util::toJsonSimple($v):$v)."\n";
                        }
                    }
                }
                break;
            case self::type_echo:
                $ret='';
                break;
            default:
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
    public function fixTplFilename($strTpl)
    {
        switch ($this->renderType){
            case self::type_www:return str_replace('.phtml', '.www.phtml', $strTpl);
            case self::type_wap:return str_replace('.phtml', '.wap.phtml', $strTpl);
            case self::type_json:return;
            case self::type_jsonp:return;
            case self::type_echo:return;
            case self::type_cmd:return;
            default: throw new \ErrorException('render type not support:'.$this->renderType) ;
        }
    }
}

