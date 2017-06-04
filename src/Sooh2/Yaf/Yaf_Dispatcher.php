<?php
class Yaf_Dispatcher
{
	public static function getInstance(){return new Yaf_Dispatcher;}
	public function disableView(){return true;}
	public function enableView(){return true;}
	/**
	 * 开启/关闭自动渲染功能. 在开启的情况下(Yaf默认开启), Action执行完成以后, Yaf会自动调用View引擎去渲染该Action对应的视图模板.
	 * @param boolean $switch
	 * @return \Yaf_Dispatcher or FALSE
	 */
	public function autoRender( $switch){return $this;}
	
	public function getApplication(){return new Yaf_Application;}
	/**
	 * 
	 * @return \Yaf_Request_Abstract
	 */
	public function getRequest(){return new Yaf_Request_Abstract;}
	/**
	 * 
	 * @return \Yaf_Router
	 */
	public function getRouter(){return new Yaf_Router;}
	
	/**
	 * 
	 * @param Yaf_Plugin_Abstract $plugin
	 * @return \Yaf_Dispatcher or False
	 */
	public function registerPlugin($plugin){return $this;}
	
	/**
	 * 改变APPLICATION_PATH, 在这之后, 将从新的APPLICATION_PATH下加载控制器/视图, 但注意, 不会改变自动加载的路径.
	 * @param string $apppath
	 * @return \Yaf_Dispatcher
	 */
	public function setAppDirectory($apppath){return $this;}
	/**
	 * 
	 * @param Yaf_Request_Abstract $request
	 * @return \Yaf_Dispatcher or False
	 */
	public function setRequest(  $request ){return $this;}
	
	/**
	 * 
	 * @param string $tpl_dir
	 * @return \Yaf_View_Simple
	 */
	public function initView( $tpl_dir){return new \Yaf_View_Simple;}
}