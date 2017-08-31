<?php

class Yaf_Controller_Abstract
{
	/**
	 * @var Yaf_Request_Abstract
	 */  
    protected $_request;

	/**
	 * @var Yaf_Response_Abstract
	 */
    protected $_response;

	/**
	 * @var Yaf_View_Simple
	 */
    protected $_view;
	/**
	 * 视图文件的目录, 默认值由Yaf_Dispatcher保证, 可以通过Yaf_Controller_Abstract::setViewPath来改变这个值
	 * @var string 
	 */
	protected $_script_path;
	public function initBySooh($request,$view)
	{
	    $this->_request = $request;
	    $this->_view = $view;
	}
	/**
	 * 初始化
	 */
	public function init(){}

	/**
	 * @return Yaf_Request_Abstract 
	 */
	public function getRequest(){}
	/**
	 * @return Yaf_Response_Abstract 
	 */
	public function getResponse(){}
	/**
	 * @return Yaf_View_Interface 
	 */
	public function getView() {}
	/**
	 * 启动view
	 * @return Yaf_View_Interface 
	 */
	public function initView(){}
	/**
	 * @param  string $view_directory view的模板路径
	 * @retrun boolean
	 */
	public function setViewPath( $view_directory){return empty($view_directory);}
	/**
	 * 获取viewPath
	 * @return string view-path
	 */
	public function getViewPath(){}
    
}
