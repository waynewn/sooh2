<?php
namespace Sooh2\BJUI;
/**
 * 基于B-JUI1.31做了一层封装，当只做一个简单标准管理后台的时候，更方便一些
 * 定死：编码utf8
 *
 * @author simon.wang
 */
class Broker 
{
    /**
     * 获取BJUI的处理类
     * @return \Sooh2\BJUI\Broker
     */
    public static function getInstance()
    {
        if(self::$_instance==null){
            self::$_instance = new Broker();
            self::$_instance->ini = new Ini;
        }
        return self::$_instance;
    }
    protected static $_instance=null;
    /**
     *
     * @var \Sooh2\BJUI\Ini 
     */
    public $ini;

    /**
     * 初始化
     * @param string $prjName 项目名称
     * @param string $actLoginOutside 处理登入请求的地址，接收loginname，passwd，asTimeout(0:外部，1：内部超时重新登入)，validcode(可选)
     * @param string $actValidImage 登入界面生成验证码的地址
     * @param string $bjui_basedir 框架资源文件路径，默认 /B-JUI/
     * @return $this
     */
    public function init($prjName,$actLoginOutside,$actValidImage,$bjui_basedir='/B-JUI/')
    {
        $this->ini->title = $prjName;
        $this->ini->loginUrlOutter=$actLoginOutside;
        $this->ini->vimgUrl = $actValidImage;
        $this->ini->basedir=$bjui_basedir;
        return $this;
    }
    public function setResultRelogin($view,$errmsg,$code=301)
    {
        $view->assign('statusCode',$code);
        if(!empty($errmsg)){
            $view->assign('message',$errmsg);
            return array('statusCode'=>301,'message'=>$errmsg);
        }else{
            return array('statusCode'=>301);
        }
    }
    public function setResultError($view,$errmsg,$code=300)
    {
        \Sooh2\Misc\ViewExt::getInstance()->initRenderType('json');
        $view->assign('statusCode',$code);
        $view->assign('message',$errmsg);
    }
    /**
     * 设置bjui框架下的操作成功的状态码
     * @param type $view
     * @param type $msg
     * @param type $closeDlgAndRefresh
     */
    public function setResultOk($view,$msg=null,$closeDlgAndRefresh=false)
    {
        \Sooh2\Misc\ViewExt::getInstance()->initRenderType('json');
        $view->assign('statusCode',200);
        if(!empty($msg)){
            $view->assign('message',$msg);
        }
        if($closeDlgAndRefresh){
            $view->assign('closeCurrent',true);
            $uri = \Sooh2\Misc\Uri::getInstance();
            $view->assign('tabid',$uri->currentModule().'-'.$uri->currentController());
        }
    }

    /**
     * 设置bjui框架下的操作成功的状态码2
     * @desc 返回成功但不关闭不刷新页面
     * @param type $view
     * @param type $msg
     * @param type $closeDlgAndRefresh
     */
    public function setResultOk2($view,$msg=null)
    {
        \Sooh2\Misc\ViewExt::getInstance()->initRenderType('json');
        //设置成201 B-JUI不自动刷新
        $view->assign('statusCode',201);
        if(!empty($msg)){
            $view->assign('message',$msg);
        }
    }

    /**
     *
     * @var \Sooh2\DB\Pager 
     */
    protected $_pager;
    /**
     * @return \Sooh2\DB\Pager
     */
    public function getPager($requestWithGetMethod)
    {
        if($this->_pager==null){
            $pageSize = $requestWithGetMethod->get('pageSize');
            $pageSizes = array(20,50,100);
            if(!in_array($pageSize, $pageSizes)){
                $pageSizes[]=$pageSize;
            }
            $this->_pager = new \Sooh2\DB\Pager($pageSize,$pageSizes);
            $this->_pager->init(-1, $requestWithGetMethod->get('pageCurrent'));
        }
        return $this->_pager;
    }
    public function assignPagerToView($view,$pager=null)
    {
        if($pager==null){
            $view->assign('pageCurrent',$this->_pager->pageid());
            $view->assign('total',$this->_pager->total);
            $view->assign('pageSize',$this->_pager->page_size);
        }
    }
}
