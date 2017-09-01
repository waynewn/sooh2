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
        if($argv!==null){//命令行模式
            parse_str($argv,$_GET);
            
            if(isset($_GET['request_uri'])){
                $temp = explode('?', $_GET['request_uri']);
                
                $_GET['request_uri']=$temp[0];
                $mca = explode('/', trim($_GET['request_uri'],'/'));
                $_GET = array();
                while(sizeof($mca)>=4){
                    $v = array_pop($mca);
                    $k = array_pop($mca);
                    $_GET[$k]=$v;
                }
                if(!empty($temp[1])){
                    parse_str($temp[1],$temp);
                    foreach($temp as $k=>$v){
                        $_GET[$k]=$v;
                    }
                }
                
                if(sizeof($mca)==2){
                    array_unshift($mca, 'default');
                }
            }
        }else{
            if(SOOH_ROUTE_VAR){//变量模式
                if(!isset($_GET[SOOH_ROUTE_VAR])){
                    if(isset($_POST[SOOH_ROUTE_VAR])){

                        $mca = explode('/', trim($_POST[SOOH_ROUTE_VAR],'/'));
                    }else{
                        $requri = array_shift(explode('?',$_SERVER['REQUEST_URI']));
                        $mca = explode('/', trim($requri,'/'));
                        if(defined(SOOH_ROUTE_VAR)){
                            define('SOOH_ROUTE_VAR', 0);
                        }
                    }
                }else{
                    $mca = explode('/', trim($_GET[SOOH_ROUTE_VAR],'/'));
                }
                unset($_GET[SOOH_ROUTE_VAR]);
            }else{//目录模式
                $requri = array_shift(explode('?',$_SERVER['REQUEST_URI']));
                $mca = explode('/', trim($requri,'/'));
                while(sizeof($mca)>=4){
                    $v = array_pop($mca);
                    $k = array_pop($mca);
                    $_GET[$k]=$v;
                }
            }
        }

        if(sizeof($mca)==2){//yaf默认的module是index
            array_unshift($mca, DEFAULT_MODULE_NAME);
        }
        

        
        if(class_exists('\Yaf_Request_Simple',false)){
            $req = new \Yaf_Request_Simple();
        }else{
            $req = new \Yaf_Request_Abstract();
        }
        $req->setModuleName($mca[0]);
        $req->setControllerName($mca[1]);
        $req->setActionName($mca[2]);

        return $req;
    }
    

}