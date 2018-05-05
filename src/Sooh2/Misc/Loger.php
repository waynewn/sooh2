<?php
namespace Sooh2\Misc;
/**
 * 日志记录（copy from github/sooh2）
 * 使用方法：
 *      1）初始化实例，参看 getInstance() 方法
 *      2）如果是要自定义文件名路径名以及分割方式，调用 initFileTpl()设置,具体参看函数说明
 *      3）当一个新的请求开始时，调用initOnNewRequest()
 *      4）根据需要，尽可能早的设置其他信息，
 *              比如用户信息：initSession() 
 *              比如其他自定义信息：initMoreInfo()
 *      5）需要记录日志的地方，调用：
 *              app_common() 常规日志（应用级）,不受tracelevel限制
 *              app_trace()  跟踪日志（应用级）
 *              app_error()错误报警日志（应用级）,不受tracelevel限制
 *              lib_trace()  跟踪日志（库级）
 *              lib_error()错误报警日志（库级）,不受tracelevel限制
 *              sys_trace()  跟踪日志（系统底层）
 *              sys_error()错误报警日志（系统底层） ,不受tracelevel限制
 *          2个参数，其中一个可以是非字符串标量的数组之类的，将被var_export方式输出
 * 
 * 其他：可以通过 traceLevel()获取和设置新的tracelevel
 *      除了common,其他的日志，都会记录当时是哪个类的哪个函数触发的
 * 
 * 输出日志格式：
 * 时间信息 ## 日志信息 ## 其他自定义信息 ## 用户、环境、进程信息  ## 触发位置函数信息以及类数组信息
 */
class Loger
{
    const trace_sys = 1;
    const trace_lib = 2;
    const trace_app = 4;    
    protected $traceLevel=0;
    protected static $_instance;
    /**
     * 初始化、获取Loger实例
     * 
     * 如果是获取实例，参数为null，获取设置好的实例
     * 如果是初始化，分两种：
     *   （参数是兼容的实例）使用兼容的实例，初始化作为本类的instance实例
     *   （参数是日志级别）生成本类的实例
     * 
     *   日志的级别（记录哪些日志）   
     *          1）system
     *          2）lib
     *          3）system 和 lib   
     *          4）application
     *          5）application & system
     *          6）application & lib
     *          7）all 
     *     
     * @param mixed $newInstance_or_traceLevel 
     * @return Loger
     */
    public static function getInstance($newInstance_or_traceLevel=null)
    {
        if($newInstance_or_traceLevel!=null){
            if(is_int($newInstance_or_traceLevel)){
                self::$_instance = new Loger();
                self::$_instance->traceLevel($newInstance_or_traceLevel);
            }else{
                self::$_instance = $newInstance_or_traceLevel;
            }
        }else{
            if(empty(self::$_instance)){
                self::$_instance = new Loger();
                self::$_instance->traceLevel(7);
            }
        }
        return self::$_instance;
    }
    //-----------------------------------------记录请求时的环境，用户等其他信息，尽可能早调用
    protected $env=array();
    /**
     * 开始处理一个新的请求的时候，调用此函数完成一些初始化（创建当前请求的唯一标识）
     */
    public function initOnNewRequest($uri)
    {
        $dt = time();
        $this->env=array('sooh2_the_req_uri'=>$uri,'sooh2_the_proc_sn'=>md5($dt.rand(1000000,9999999)));
        $this->initOnNewRequest_fileTpl($dt);
        return $this;
    }
    /**
     * 记录当前请求的会话sessionid 和 userid, 给null的时候不记录，给 ''记录
     * @param type $sessId 给null的时候不记录，给 ''记录
     * @param type $userId 给null的时候不记录，给 ''记录
     */
    public function initSession($sessId,$userId)
    {
        if($sessId!==null){
            $this->env['sess']=$sessId;
        }
        if($userId!==null){
            $this->env['user']=$userId;
        }
        return $this;
    }
    /**
     * 记录更多信息
     * @return \Sooh2\Misc\Loger
     */
    public function initMoreInfo($k,$v)
    {
        $this->env[$k]=$v;
        return $this;
    }
    
    
    /////////////////////////////////////////////文本日志 的文件名路径名设置
    protected $fileTpl=array();
    protected $fileThisRequest=array();
    /**
     * 自定义输出文件的路径、名称或拆分方式
     * 
     * 定义中支持的参数：{year},{month},{day},{hour},{minute},{second},{type}
     * 
     * @param type $basedir  基础路径
     * @param type $filenameWithSubDir  文件名，可以带子路径
     * @return Loger
     */
    public function initFileTpl($basedir=null,$filenameWithSubDir=null)
    {
        $this->fileTpl = array('dir'=>$basedir,'file'=>$filenameWithSubDir);
        return $this;
    }
    /**
     * 当新请求开始时，生成本次请求的文件路径
     * @param int $timeStamp 时间戳
     */
    protected function initOnNewRequest_fileTpl($timeStamp)
    {
        if(empty($this->fileTpl)){
            return;
        }
        $s = date('Y m d H i s',$timeStamp);
        $tmp = explode(' ',$s);

        if($this->fileThisRequest){
            $this->fileThisRequest=array();
            $tmp[6]='common';
            $this->fileThisRequest['common'] = str_replace(
                   array('{year}','{month}','{day}','{hour}','{minute}','{second}','{type}'), 
                   $tmp, 
                   $this->dir.'/'.$this->filename);
            $tmp[6]='error';
            $this->fileThisRequest['error'] = str_replace(
                   array('{year}','{month}','{day}','{hour}','{minute}','{second}','{type}'), 
                   $tmp, 
                   $this->dir.'/'.$this->filename);
            $tmp[6]='trace';
            $this->fileThisRequest['debug'] = str_replace(
                   array('{year}','{month}','{day}','{hour}','{minute}','{second}','{type}'), 
                   $tmp, 
                   $this->dir.'/'.$this->filename);
        }
    }


    protected function _writeTxtFile($func, $str)
    {
        if(empty($this->fileTpl)){
            error_log(' ## '.$str);
        }else{
            list($tracelevel, $type)=explode('_',$func);
            
            file_put_contents($this->fileThisRequest[$type], date('M-d H:i:s').' ## '.$str."\n",FILE_APPEND);
        }
    }

    /**
     * 获取或设置新的trace level
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
    
    /**
     * 获取调用位置信息
     */
    protected function getCallPosition()
    {
        $trace_begin = 2;
        $arr = debug_backtrace(null , $trace_begin + 2);
        $args = $arr[$trace_begin + 1]['args'];
        $strArgs = '';
        if(!empty($args)){
            foreach($args as $i){
                if(is_scalar($i)){
                    $strArgs.=$i.',';
                }else{
                    $strArgs.= gettype($i).',';
                }
            }
            $strArgs=substr($strArgs,0,-1);
        }
        return $arr[$trace_begin + 1]['class'].'->'.$arr[$trace_begin + 1]['function'].'['.$arr[$trace_begin]['line'].']('.$strArgs.')';


    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * 格式化日志信息 ： 
     *      ## 日志信息 ## 其他信息 ## 用户、进程信息  ## 触发位置函数信息以及类数组信息
     * @param string $flg
     * @param type $msgOrObj
     * @param type $prefixIntro
     * @return string
     */
    protected function fmtTxtLog($flg,$msgOrObj, $prefixIntro)
    {
        list($tracelevel, $type)=explode('_',$flg);
        $traceStr='';
        $traceObj='';
        if(is_null($msgOrObj)){
            $traceStr.='null';
        }if(is_scalar($msgOrObj)){
            $traceStr.=$msgOrObj;
        }else{
            $traceObj = var_export($msgOrObj,true);
        }
        if(is_null($prefixIntro)){
            $traceStr.='null';
        }if(is_scalar($prefixIntro)){
            $traceStr.=$prefixIntro;
        }else{
            $traceObj = var_export($prefixIntro,true);
        }
        if($flg!=='app_common'){
            $traceCallPos = $this->getCallPosition();
        }else{
            $traceCallPos='';
        }
        
        $tmp = $this->env;
        $traceUserProcessInfo ='';
        if(isset($tmp['user'])){
            $traceUserProcessInfo.='User:'.$tmp['user'].', ';
            unset($tmp['user']);
        }
        if(isset($tmp['sess'])){
            $traceUserProcessInfo.='SESS:'.$tmp['sess'].', ';
            unset($tmp['sess']);
        }
        if(isset($tmp['sooh2_the_req_uri'])){
            $traceUserProcessInfo.='Uri:'.$tmp['sooh2_the_req_uri'].', ';
            unset($tmp['sooh2_the_req_uri']);
        }
        if(isset($tmp['sooh2_the_proc_sn'])){
            $traceUserProcessInfo.='P_SN:'.$tmp['sooh2_the_proc_sn'].', ';
            unset($tmp['sooh2_the_proc_sn']);
        }
        $otherTraceInfo = $type;
        foreach ($tmp as $k=>$v){
            $otherTraceInfo.=" $k:$v";
        }
        //## 日志信息 ## 其他信息 ## 用户、进程信息  ## 触发位置函数信息以及类数组信息
        return $traceStr.' ## '.$otherTraceInfo.' ## '.$traceUserProcessInfo.' ## '.$traceCallPos." ".$traceObj;
    }

    public function sys_trace($msgOrObj,$moreIntro=null)
    {
        if($this->traceLevel & self::trace_sys){
            $this->_writeTxtFile(__FUNCTION__, $this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
        }
    }
    /**
     * use sys_error instead
     * @deprecated 
     */    
    public function sys_warning($msgOrObj,$moreIntro=null)
    {
        $this->sys_error(__FUNCTION__,$msgOrObj, $moreIntro);
    }
    public function sys_error($msgOrObj,$moreIntro=null)
    {
        $this->_writeTxtFile(__FUNCTION__, $this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }
    public function app_common($msgOrObj,$moreIntro=null)
    {
        $this->_writeTxtFile(__FUNCTION__, $this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }
    
    public function app_trace($msgOrObj,$moreIntro=null)
    {
        if($this->traceLevel & self::trace_app){
            $this->_writeTxtFile(__FUNCTION__, $this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
        }
    }
    /**
     * use app_error instead
     * @deprecated 
     */
    public function app_warning($msgOrObj,$moreIntro=null)
    {
        $this->app_error(__FUNCTION__,$msgOrObj, $moreIntro);
    }
    public function app_error($msgOrObj,$moreIntro=null)
    {
        $this->_writeTxtFile(__FUNCTION__,$this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }
    public function lib_trace($msgOrObj,$moreIntro=null)
    {
        if($this->traceLevel & self::trace_lib){
            $this->_writeTxtFile(__FUNCTION__,$this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
        }
    }
    /**
     * use lib_error instead
     * @deprecated 
     */
    public function lib_warning($msgOrObj,$moreIntro=null)
    {
        $this->lib_error(__FUNCTION__,$msgOrObj, $moreIntro);
    }
    public function lib_error($msgOrObj,$moreIntro=null)
    {
        $this->_writeTxtFile(__FUNCTION__,$this->fmtTxtLog(__FUNCTION__,$msgOrObj, $moreIntro));
    }    
}