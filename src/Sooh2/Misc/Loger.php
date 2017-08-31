<?php
namespace Sooh2\Misc;
class Loger
{
    public $trace_begin = 2;//debug_backtrace 起始的位置

    public $prefixIntro = ''; //全局的前缀
    /**
     * 设置记录当前进程信息的标示（默认会尝试 ' # dev_'.ini->getRuntime(deviceId).' usr_'.ini->getRuntime(userId).' pid_'.getmypid().' srv_'.ini->getServerId()）
     * @param string $str 指定格式
     * @return \Sooh2\Misc\Loger
     */
    public function initRuntimeInfo($sessionid=null,$serverId=0)
    {
        $this->env['LogSess']=$sessionid;
        $this->env['LogPros']=getmypid().'@'.$serverId;
        return $this;
    }
    /**
     * 
     * @return \Sooh2\Misc\Loger
     */
    public function initMoreInfo($k,$v)
    {
        $this->env[$k]=$v;
        return $this;
    }
    protected static $_instance;
    /**
     * 获取或设置Loger (首次执行进行设置时建议接着调用initProcessIdentifier)
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
                self::$_instance->traceLevel($newInstance_or_traceLevel);
            }else{
//                error_log('create loger by new instance');
//                $err = new \ErrorException();
//                error_log($err->getTraceAsString());
                self::$_instance = $newInstance_or_traceLevel;
            }
        }else{
            if(empty(self::$_instance)){
                self::$_instance = new Loger();
                self::$_instance->traceLevel(0);
            }
        }
        return self::$_instance;
    }
    const trace_sys = 1;
    const trace_lib = 2;
    const trace_app = 4;
    protected $traceLevel=0;
    /**
     * 获取或设置trace level
     * @param type $newLv
     * @return type
     */
    public function traceLevel($newLv=null)
    {
        if($newLv===null){
            return $this->traceLevel;
        }else{
            $this->traceLevel = $newLv;
        }
    }
    protected $_processId=' ';
    protected $env=array('LogType'=>'');
    protected function fillTypeAndCallPos($flg)
    {
        $arr = debug_backtrace(null , $this->trace_begin + 2);
        $callPosition = $arr[$this->trace_begin + 1]['class'].'->'.$arr[$this->trace_begin + 1]['function'].'['.$arr[$this->trace_begin]['line'].'](...)';
        $this->env['LogCall']=$callPosition;
        $this->env['LogType']=$flg;
    }

    protected function fmtTxtLog($flg,$msgOrObj, $prefixIntro)
    {
        $prefixIntro = $this->prefixIntro . $prefixIntro;

        $this->fillTypeAndCallPos($flg);
        $buf = '';
        foreach($this->env as $k=>$v){
            $buf.=$k.':'.$v.' ';
        }
        $envInfo= '['.substr($buf,0,-1).']';
        
        if(is_array($msgOrObj)){
            $msg = ($prefixIntro?:'').' '.\Sooh2\Util::toJsonSimple($msgOrObj)." ".$envInfo;
        }elseif (is_object($msgOrObj)) {
            $msg = ($prefixIntro?:'')." ".$envInfo."\n".var_export($msgOrObj , true);
        }else {
            $msg = ($prefixIntro?:'').' '.$msgOrObj." ".$envInfo;
        }
        return trim($msg);
    }
    protected function fmtDBLog($flg,$msgOrObj, $prefixIntro)
    {
        $this->fillTypeAndCallPos($flg);
        if(is_array($msgOrObj)){
            $msg = ($prefixIntro?:'').' '.\Sooh2\Util::toJsonSimple($msgOrObj);
        }elseif (is_object($msgOrObj)) {
            $msg = ($prefixIntro?:'')."\n".var_export($msgOrObj , true);
        }else {
            $msg = ($prefixIntro?:'').' '.$msgOrObj;
        }
        return array_merge($this->env,array('msg'=>$msg));
    }
    public function sys_trace($msgOrObj,$moreIntro=null)
    {
        if($this->traceLevel & self::trace_sys){
            error_log($this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
        }
    }
    public function sys_warning($msgOrObj,$moreIntro=null)
    {
        error_log($this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }
    
    public function app_trace($msgOrObj,$moreIntro=null)
    {
        if($this->traceLevel & self::trace_app){
            error_log($this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
        }
    }
    public function app_warning($msgOrObj,$moreIntro=null)
    {
        error_log($this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }
    public function lib_trace($msgOrObj,$moreIntro=null)
    {
        if($this->traceLevel & self::trace_lib){
            error_log($this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
        }
    }
    public function lib_warning($msgOrObj,$moreIntro=null)
    {
        error_log($this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }
}