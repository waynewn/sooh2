<?php
namespace Sooh2\HTML\Item;
/**
 * 输入控件的基类
 *
 * @author simon.wang
 */
class Base {
    protected $_name;
    /**
     *
     * @var \Sooh2\Valid\Base 
     */
    protected $_checker=null;
    /**
     * 数组或数据的url 
     */
    protected $_optionsData;
    protected $_val;
    protected $_capt;
    protected $_cssStyleAndClass='';
    protected $_otStr; //额外的配置 比如readonly disable
    /**
     * 
     * @param string $name 名称
     * @param string $defaultVal 默认值,不参与valid检查
     * @param string $capt 显示的标题
     * @return \Sooh2\HTML\Item\Base
     */
    public static function factory($name,$defaultVal,$capt , $otStr = ''){
        $c = get_called_class();
        $inputitem = new $c;
        $inputitem->_name=$name;
        $inputitem->setValue($defaultVal);
        $inputitem->_capt=$capt;
        $inputitem->_otStr = $otStr;
        return $inputitem;
    }
    /**
     * 设置检查条件
     * @param \Sooh2\Valid\Base $chk
     * @return \Sooh2\HTML\Item\Base
     */
    public function initChecker($chk)
    {
        $this->_checker = $chk;
        return  $this;
    }
    /**
     * 设置其他的数据来源（数组或数据的url）
     * @param mixed $options （数组或数据的url）
     * @param string $enableAll 给个字符串作为全部的对应显示（value=''）
     * @return \Sooh2\HTML\Item\Base
     */
    public function initOptions($options,$enableAll=false)
    {
        $this->_optionsData = $options;
        if($enableAll!== false){
            $this->_optionsData[$enableAll]='全部';
        }
        return  $this;
    }

    public function getArgName(){return $this->_name;}
    public function isConsts(){return false;}
    public function getValue(){return $this->_val;}
    /**
     * 
     * @param mixed $v
     * @throws Exception
     * @return \Sooh2\HTML\FormItem
     */
    public function setValue($v){
        $this->_val=$v;
        return $this;
    }
    /**
     * 复杂的输入控件，覆盖这里的方法，完成检查，拼装value
     * @param mixed $req request_with_get_method or value
     * @return mixed 错误描述 或 false（没有错误）
     */
    public function chk($req)
    {
        if(is_scalar($req) || is_array($req)){
            $valInput = $req;
        }else{
            $valInput = $req->get($this->getArgName());
        }
        if(!is_array($valInput)){
            $valInput = trim($valInput);
        }
        if($this->_checker){
            $err =  $this->_checker->check($this->_capt,$valInput);
            if(!$err){
                $this->setValue($valInput);
            }
            return $err;
        }else{
            $this->setValue($valInput);
            return false;
        }
    }
    public function render($tpl=null){}
}
