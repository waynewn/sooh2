<?php
class Yaf_Request_Simple
{
	/**
	 * @return string ModuleName
	 */
	public function getModuleName(){return \Sooh2\Misc\Ini::getInstance()->getRuntime('req.module');;}
	/**
	 * @return string ControllerName
	 */
	public function getControllerName(){return \Sooh2\Misc\Ini::getInstance()->getRuntime('req.controller');}
    /**
	 * @return string ActionName
	 */
	public function getActionName(){return \Sooh2\Misc\Ini::getInstance()->getRuntime('req.action');}
    
	/**
	 * @param string $name module-name
	 * @return boolean
	 */
	public function setModuleName($name){\Sooh2\Misc\Ini::getInstance()->setRuntime('req.module',$name);return true;}
    
	/**
	 * @param string $name controller-name
	 * @return boolean
	 */
	public function setControllerName($name){\Sooh2\Misc\Ini::getInstance()->setRuntime('req.controller',$name);return true;}
	
	/**
	 * @param string $name action-name
	 * @return boolean
	 */
	public function setActionName($name){\Sooh2\Misc\Ini::getInstance()->setRuntime('req.action',$name);return true;}

	/**
	 * 初始化
	 */
	public function init(){}
	/**
	 * @return \Exception 
	 */
	public function getException(){throw new \ErrorException('not support');}
	public function get($name,$default= NULL){
	    if(isset($_GET[$name])){
	        return $_GET[$name];
	    }elseif(isset($_POST[$name])){
	        return $_POST[$name];
        }elseif(isset($this->_params[$name])){
            return $this->_params[$name];
	    }
	    return $default;
	}
	protected $_params=array();
	/**
	 * @return array
	 */
	public function getParams (){return array_merge($_GET,$_POST,$this->_params);}
	/**
	 * @param string $name 变量名
	 * @param mixed $default 当空值的时候用$default替代
	 * @return mixed
	 */
	public function getParam($name,  $default= NULL){return isset($this->_params[$name])?$this->_params[$name]:$default;}
	/**
	 * @param string $name 变量名
	 * @param mixed $value 当空值的时候用$default替代
	 * @return Yaf_Request_Abstract
	 */
	public function setParam($name,  $value){$this->_params[$name]=$value;return $this;}
	/**
	 * @return string 可能的返回值为GET,POST,HEAD,PUT,CLI等
	 */
	public function getMethod( ){return $_SERVER['REQUEST_METHOD'];}
	
	public function getLanguage(){throw new \ErrorException('not support');}
	public function getQuery($name= NULL){throw new \ErrorException('not support');}
	public function getPost($name= NULL){throw new \ErrorException('not support');}
	public function getEnv($name= NULL){throw new \ErrorException('not support');}
	public function getServer($name= NULL){return $name===null?$_SERVER:$_SERVER[$name];}
	public function getCookie($name= NULL){return $name===null?$_COOKIE:$_COOKIE[$name];}
	public function getFiles($name= NULL){throw new \ErrorException('not support');}
	public function isGet(){throw new \ErrorException('not support');}
	public function isPost(){throw new \ErrorException('not support');}
	public function isHead(){throw new \ErrorException('not support');}
	public function isXmlHttpRequest(){throw new \ErrorException('not support');}
	public function isPut(){throw new \ErrorException('not support');}
	public function isDelete(){throw new \ErrorException('not support');}
	public function isOption(){throw new \ErrorException('not support');}
	public function isCli(){throw new \ErrorException('not support');}
	
	public function isDispatched(){throw new \ErrorException('not support');}
	public function setDispatched(){throw new \ErrorException('not support');}
	public function isRouted(){throw new \ErrorException('not support');}
	public function setRouted(){throw new \ErrorException('not support');}

}