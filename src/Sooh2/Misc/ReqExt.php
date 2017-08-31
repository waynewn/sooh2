<?php
namespace Sooh2\Misc;

/**
 * request 辅助类：
 * 1）处理json格式的raw-data，
 * 2）其它request相关的初始化，比如记录当前的mca，状态字段列表，默认输出格式等
 * 3）初始需要 define 的环境参数
define('SOOH_ROUTE_VAR','["m","c","a"]');//定义uri router 工作模式，参看 \Sooh2\Misc\Uri里routeByVar的说明
define('DEFAULT_RENDER_TYPE','json');//默认输入格式
define('ARGNAME_RENDER_TYPE','__VIEW__');//手动指定返回格式的参数名，如果是jsonp还需要通过 __VIEW__arg 指定回调函数名
define('ARGNAME_STATUSNOW','extendInfo');//额外获取状态的列表
define('FORCE_MCA','/m/c/a');可选，如果需要强制设置mca的话，定义这个，适用场景：接口拦截代理
 * 
 * @author simon.wang
 */
class ReqExt {
    /**
     * 检查是否是攻击行为
     * @param int $argc 对应命令行模式下的argc
     */
    public static function checkAttackingRequest($argc=0)
    {
        if($argc<2 && $_SERVER['HTTP_CONTENT_TYPE']!=='application/json'){//是否命令行格式或raw
            if(empty($_GET) && empty($_POST) ){
                list($k,$v) = explode('?', $_SERVER['REQUEST_URI']);
                $v = explode('/', $k);
                if(sizeof($v)==1){
                    error_log('attacking??'.$_SERVER['REMOTE_ADDR']);
                    exit;
                }
            }
        //    elseif(isset($_REQUEST[SOOH_ROUTE_VAR])){             //如果是 SOOH_ROUTE_VAR 模式
        //        if(explode('/', $_REQUEST[SOOH_ROUTE_VAR])==1){
        //            error_log('attacking??'.$_SERVER['REMOTE_ADDR']);
        //            exit;
        //        }
        //    }
        }
    }
    /**
     * 系统route完成，生成分派任务Controller之前调用
     * @param type $reqObj
     */
    public static function beforeDispatch($reqObj)
    {
        $s = get_called_class();
        $o = new $s;
        $o->parseRawData($reqObj);
        $o->resetMCA($reqObj);
        $o->lastTodo($reqObj);
    }
    /**
     * 解析rawdata, 并赋予request对象
     * @param type $req
     * @return $this
     */
    protected function parseRawData($req)
    {
        $rawData = file_get_contents('php://input');
        if(!empty($rawData)){//有raw-data
            $r = json_decode($rawData,true);
            if(!is_array($r)){//目前仅支持json的rawdata
                \Sooh2\Misc\Loger::getInstance()->app_warning('invalid rawdata received:'.$rawData);
                return $this;
            }
            foreach($r as $k=>$v){
                $this->moreToReq($req, $k, $v);
            }

        }
        return $this;
    }
    /**
     * 把rawdata里的数据设置到request里（根据框架使用的request类选择方法记录）
     * @param type $req
     */
    protected function moreToReq($req,$k,$v)
    {
        if($k=='data'){
            foreach($v as $i=>$r){
                $req->setParam($i,$r);
            }
        }else{
            $req->setParam($k,$v);
        }
    }
    /**
     * 根据需要重新设置MCA(根据框架使用的request类选择方法记录)
     * @param \Yaf_Request_Abstract $req
     */
    protected function resetMCA($req)
    {
        if(defined('FORCE_MCA')){
            $r = explode('/', trim(FORCE_MCA,'/'));
            $req->setModuleName($r[0]);
            $req->setControllerName($r[1]);
            $req->setActionName($r[2]);
        }
        return $this;
    }
    /**
     * 最后做的事情：
     * 1）\Sooh2\Misc\Uri 记录当前请求的mca是什么，以及工作模式
     * 2）\Sooh2\Misc\ViewExt 记录最后输出的追加的状态需求
     * 3）\Sooh2\Misc\ViewExt 根据是否命令行模式、是否手动指定了输出格式，设置系统的默认输出格式
     * @param type $req
     * @return $this
     */
    protected function lastTodo($req)
    {
        //初始化uri
        $module = $req->getModuleName();
        $ctrl = $req->getControllerName();
        $act = $req->getActionName();
        $conf = \Sooh2\Misc\Ini::getInstance()->getIni('application.product');
        if(is_string(SOOH_ROUTE_VAR)){
            $tmp = json_decode(SOOH_ROUTE_VAR,true);
            if(is_array($tmp)){
                \Sooh2\Misc\Uri::getInstance()->initMCA($module, $ctrl, $act)->init($conf['application.baseUri'],$tmp,'index.php');
            }else{
                \Sooh2\Misc\Uri::getInstance()->initMCA($module, $ctrl, $act)->init($conf['application.baseUri'],SOOH_ROUTE_VAR,'index.php');
            }
        }else{
            \Sooh2\Misc\Uri::getInstance()->initMCA($module, $ctrl, $act)->init($conf['application.baseUri'],false,'index.php');
        }
        
        //设置追加状态列表
        $r = $req->get(ARGNAME_STATUSNOW);
        if(!empty($r)){
            if(is_string($r)){
                $r = explode(',', $r);
            }
            $vw = \Sooh2\Misc\ViewExt::getInstance();
            foreach($r as $z){
                $vw->initStatusTaskList('\\Prj\\RefreshStatus\\'.$z);
            }
        }
        $viewlikeyaf = \Sooh2\Misc\ViewExt::getInstance();
        //设置默认输出格式
        $viewlikeyaf->initRenderType($req->get(ARGNAME_RENDER_TYPE,DEFAULT_RENDER_TYPE),$req->get(ARGNAME_RENDER_TYPE.'_arg'));
        return $this;
    }
}
