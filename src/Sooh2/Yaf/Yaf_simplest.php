<?php
namespace Sooh2\Yaf;
if(!class_exists('\Yaf_Controller_Abstract')){
    include __DIR__.'/Yaf_Controller_Abstract.php';
    include __DIR__.'/Yaf_Request_Abstract.php';
    include __DIR__.'/Yaf_View_Simple.php';
}
class Yaf_simplest
{
    /**
     * @return \Yaf_Request_Abstract
     */
    public static function getRequest($argv=null)
    {
        if($argv!==null){
            parse_str($argv,$_GET);
            if(isset($_GET['request_uri'])){
                $mca = explode('/', trim($_GET['request_uri'],'/'));
                $_GET = array();
                while(sizeof($mca)>=4){
                    $v = array_pop($mca);
                    $k = array_pop($mca);
                    $_GET[$k]=$v;
                }
                if(sizeof($mca)==2){
                    array_unshift($mca, 'default');
                }
            }
            \Sooh2\Misc\ViewExt::$renderType = \Sooh2\Misc\ViewExt::type_json;
            
        }
        if(isset($_GET[SOOH_ROUTE_VAR])){
            $mca = explode('/', trim($_GET[SOOH_ROUTE_VAR],'/'));
            if(sizeof($mca)==2){
                array_unshift($mca, 'default');
            }
            unset($_GET[SOOH_ROUTE_VAR]);
        }else{
            \Sooh2\Misc\Loger::getInstance()->sys_warning('todo:// likeyaf 里处理其他路由模式');
            //todo:其它路由模式
        }
        
        
        if(class_exists('\Yaf_Request_Simple',false)){
            $req = new \Yaf_Request_Simple();
        }else{
            $req = new \Yaf_Request_Abstract();
        }
        $req->setModuleName($mca[0]);
        $req->setControllerName($mca[1]);
        $req->setActionName($mca[2]);
//         foreach ($_GET as $k=>$v){
//             $req->setParam($k, $v);
//         }
//         foreach ($_POST as $k=>$v){
//             $req->setParam($k, $v);
//         }

        return $req;
    }
    
    public static function fillRequestMCA($req,$routeVAR)
    {

    }
}