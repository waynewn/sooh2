<?php
namespace Sooh2\Misc;
class Loger
{
    protected static $_instance;
    /**
     * 获取或设置Loger 
     *   traceLevel： 按位或确认记录trace日志的级别   1）系统库  2）lib   4）应用       
     *   $newInstance_or_traceLevel = null： 获取当前的实例
     *   $newInstance_or_traceLevel = 数字： 初始化为使用sooh基本loger类, trace等级由$newInstance_or_traceLevel定
     *   $newInstance_or_traceLevel = loger实例： 初始化为使用自定义loger实例
     * @param mixed $newInstance_or_traceLevel 
     * @return \Sooh2\Misc\Loger
     */
    public static function getInstance($newInstance_or_traceLevel=null)
    {
        if($newInstance_or_traceLevel!=null){
            if(is_int($newInstance_or_traceLevel)){
                self::$_instance = new Loger();
                self::$_instance->traceLevel = $newInstance_or_traceLevel;
                self::$_instance->processIdentifer();
            }else{
                self::$_instance = $newInstance_or_traceLevel;
            }
        }else{
            if(empty(self::$_instance)){
                self::$_instance = new Loger();
                self::$_instance->processIdentifer();
                self::$_instance->traceLevel = 0;
            }
        }
        return self::$_instance;
    }
    const trace_sys = 1;
    const trace_lib = 2;
    const trace_app = 4;
    protected $traceLevel=0;
    protected $serverId;
    protected $_processId='processId';
    protected function processIdentifer()
    {
        if(class_exists('\\Sooh2\\Misc\\Ini',false)){
            $this->serverId=Ini::getInstance()->getIni('application.serverId')-0;
        }else{
            $this->serverId = 0;
        }
        
        $this->_processId  = getmypid().'@'.$this->serverId;
    }
    public function traceLevel()
    {
        return $this->traceLevel;
    }
    public function sys_trace($msg)
    {
        if($this->traceLevel & self::trace_sys){
            error_log(__FUNCTION__.' '.$this->_processId.' '.$msg);
        }
    }
    public function sys_warning($msg)
    {
        error_log(__FUNCTION__.' '.$this->_processId.' '.$msg);
    }
    
    public function app_trace($msg)
    {
        if($this->traceLevel & self::trace_app){
            error_log(__FUNCTION__.' '.$this->_processId.' '.$msg);
        }
    }
    public function app_warning($msg)
    {
        error_log(__FUNCTION__.' '.$this->_processId.' '.$msg);
    }
    public function lib_trace($msg)
    {
        if($this->traceLevel & self::trace_lib){
            error_log(__FUNCTION__.' '.$this->_processId.' '.$msg);
        }
    }
    public function lib_warning($msg)
    {
        error_log(__FUNCTION__.' '.$this->_processId.' '.$msg);
    }
}