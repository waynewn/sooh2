<?php
namespace Sooh2\HTML\Form;

/**
 * Form的基类, getinput 的时候会过滤掉几个预定义需要特殊处理的： __pkey__和__frmCreate__
 *
 * @author simon.wang
 */
class Base {
    protected $_method;
    protected $_htmlId;
    public $_action;
    protected $_extraJS;
    protected static $_autoInc=1;
    public function __construct($actionUrl,$method='post',$htmlId=null) {
        if($htmlId===null){
            $uri = \Sooh2\Misc\Uri::getInstance();
            $htmlId='Frm_'.$uri->currentModule().'_'.$uri->currentController().'_'.$uri->currentAction().'_'.(self::$_autoInc++);
        }
        $this->_htmlId = $htmlId;
        $this->_method=$method;
        $this->_action = $actionUrl;
        $this->hiddens['__frmCreate__']=time();
    }
    public static function factory($actionUrl,$method='post',$htmlId=null)
    {
        $c = get_called_class();
        return new $c($actionUrl,$method,$htmlId);
    }
    public $items=array();
    /**
     * 
     * @param \Sooh2\HTML\Item\Base $item
     * @return \Sooh2\HTML\Form\Base
     */
    public function addFormItem($item)
    {
        $this->items[]=$item;
        return $this;
    }
    public $hiddens=array();
    /**
     * 
     * @return \Sooh2\HTML\Form\Base
     */    
    public function appendHiddenFirst($k,$v)
    {
        $this->hiddens[$k]=$v;
        return $this;
    }
    /**
     * 
     * @return \Sooh2\HTML\Form\Base
     */
    public function initNeedsExtraJS($jsFunc)
    {
        $this->_extraJS = $jsFunc;
        return $this;
    }
    public function getHtmlId()
    {
        return $this->_htmlId;
    }
    public function render($ignore=null)
    {
        $s = '<form method='.$this->_method.' action="'.$this->_action.'" id="'.$this->_htmlId.'">';
        foreach($this->items as $index => $item){
            $s.=$this->renderItem($item, $index);
        }
        
        $s.=$this->btnSubmit().'</form>';
    }
    protected function renderItem($item,$index=0)
    {
        return $item->render();
    }
    protected function btnSubmit()
    {
        return '<input type=submit>';
    }
    
    /**
     * 注意，目前的实现是：
     * a) 用户输入错误，保留默认值
     * b) 用户输入合法，用输入值覆盖item的值
     * @param \Sooh2\HTML\Item\Base $item
     * @param mixed $req
     */
    protected function fillItemValue($item,$req)
    {
        if(!$item->isConsts()){
            $id = $item->getArgName();
            $defaultVal = $item->getValue();

            $err = $item->chk($req);
            if($err){
                $this->_errors[$id]=$err;
                $this->_inputed[$id]=trim($defaultVal);
            }else{
                $this->_inputed[$id]=$item->getValue();
            }
            
        }else{
            $id = $item->getArgName();
            $this->_inputed[$id]=$item->getValue();
        }
    }

    /**
     * 获取数据输入格式检查的错误列表
     * @return type
     */
    public function getInputErrors()
    {
        return $this->_errors;
    }
    
    protected $_flgIsUserSubmit=null;
    /**
     * 本次请求是否是用户提交的,如果是，继续后续的检查过滤
     * @param request_with_get_method $requestWithGetMethod
     * @return boolean
     */
    public function isUserRequest($requestWithGetMethod)
    {
        if($this->_flgIsUserSubmit===null){
            $tmp = $requestWithGetMethod->get('__frmCreate__');
            if(empty($tmp)){
                return $this->_flgIsUserSubmit=false;
            }else{
                $this->_flgIsUserSubmit=true;
                $this->checkReqParams($requestWithGetMethod);
                foreach($this->hiddens as $k=>$v){
                    if($k!='__frmCreate__' && $k!='__pkey__'){
                        $this->_inputed[$k]=trim($v);
                    }
                }
            }

        }
        return $this->_flgIsUserSubmit;
    }
    /**
     * 检查过滤所有非hidden的输入项
     * @param request_with_get_method $requestWithGetMethod
     * @return boolean
     */
    public function checkReqParams($requestWithGetMethod)
    {
        foreach($this->items as $o){
            $this->fillItemValue($o,$requestWithGetMethod);
        }
    }
    protected $_inputed=array();
    protected $_errors=array();
    public function getErrors()
    {
        return $this->_errors;
    }
    public function getInputs()
    {
        return $this->_inputed;
    }
    public function getWhere()
    {
        $ret = array();
        if($this->_flgIsUserSubmit){
            foreach($this->_inputed as $k=>$v){
                if($v===''){
                    continue;
                }
                $cmp = substr($k,0,3);
                $k0 = substr($k,3);
                switch($cmp){
                    case 'eq_':$ret[$k0]=$v;break;
                    case 'lk_':$ret['*'.$k0]='%'.$v.'%';break;
                    case 'lt_':$ret['['.$k0]=$v;break;
                    case 'gt_':$ret[']'.$k0]=$v;break;
                    default: $ret[$k0]=$v;break;
                }
            }
        }
        return $ret;
    }
}
