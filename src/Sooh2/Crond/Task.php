<?php
namespace Sooh2\Crond;

/**
 * 定时任务执行类
 * a) standalone目录下所有的任务都会被独立执行；
 * b) 其他的有依赖关系的任务要放在同一目录下（不能取名standalone）
 * c) 加载任务只扫描第一层目录，可以为任务建立更深的子目录
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Task {
    /**
     * 调用方式 true：手动调用，false：计划任务自动调用
     * @var boolean 
     */
    protected $_isManual=false;
    /**
     * 两次调用间的间隔秒数（默认30，调用间隔不总是保证60秒）
     * @var int
     */
    protected $_secondsRunAgain=30;
    /**
     * 首次运行的时间点（hhiiss中的iiss部分）
     * @var type 
     */
    protected $_iissStartAfter=0;
    /**
     * 第几次调用_onRun函数，在_onRun内部，是从1开始的
     * @var int 
     */
    protected $_counterCalled=0;
    /**
     * 下一轮是否仍需要调用
     * @var boolean 
     */
    public $toBeContinue=false;
    public $lastMsg=null;
    /**
     *
     * @var int 
     */
    public $counterOk=0;//本轮执行一共成功几次
    /**
     *
     * @var \Sooh\Base\Crond\Ret
     */
    public $ret;
    /**
     * 内部值，记录任务是在哪个子目录下
     * @var type 
     */
    public $subdir='';

    /**
     * 
     * @param boolean $isManual 是否是手动调用的
     */
    public function __construct($isManual)
    {
        $this->_isManual=$isManual;
    }
    /**
     * 设置同一子目录下任务执行顺序，越大越往后排（0表示前置的，无顺序要求的任务）
     * @return type
     */
    public function execOrder()
    {
        return 0;
    }
    /**
     * 任务初始化函数
     */
    public function init(){/*$this->_secondsRunAgain=600;*/}
    /**
     * 资源释放函数
     */
    public function free(){}
    /**
     * 内部值：最后一次执行的时间
     */
    protected $_lastRun = 0;

    /**
     * 定时调用，返回本次调用算成功还是算失败
     * @param \Sooh2\Crond\Time $dt
     * @return boolean
     */
    public function run($dt)
    {
//		error_log("\tCrond ".  getmypid()."#\tstartAfter:".($dt->his%10000) .'>='.$this->_iissStartAfter."  lastRun:".date("md H:i:s")
//				." dur:".$this->_secondsRunAgain.' pass='.($dt->timestamp() - $this->_lastRun) . " hour:".$dt->hour." vs ".date('H',$this->_secondsRunAgain+$this->_lastRun));
        if($this->_isManual){
            $ret = $this->onRun($dt);
            if($dt->hour != date('H',$this->_secondsRunAgain+$this->_lastRun)){
                $this->toBeContinue=false;
            }
            if(!is_bool($ret)){
                    throw new \ErrorException("return of CrondTask->onRun should be boolean,".  var_export($ret,true)." given");
            }else {
                if($ret){
                    $this->counterOk++;
                }
                return $ret;
            }
        }elseif($dt->his%10000 >=$this->_iissStartAfter){
            $_tmp = $dt->timestamp();

            if($this->_lastRun==0 || $_tmp - $this->_lastRun>=$this->_secondsRunAgain){
                $this->_lastRun = $_tmp;
                $this->_counterCalled++;
                $ret = $this->onRun($dt);
                if($ret){
                    $this->counterOk++;
                }
                if($dt->hour != date('H',$this->_secondsRunAgain+$this->_lastRun)){
                    $this->toBeContinue=false;
                }
                if(!is_bool($ret)){
                        throw new \ErrorException("return of CrondTask->onRun should be boolean,".  var_export($ret,true)." given");
                }else {
                    return $ret;
                }
            }else{
                $this->lastMsg=null;
                return false;
            }
        }else{
                $this->lastMsg=null;
                return false;
        }
    }
    /**
     * 返回本次调用算成功还是算失败
     * @param \Sooh2\Crond\Time $dt
     * @return boolean
     */
    protected function onRun($dt)
    {
        return false;
    }
    /**
     * 如果在执行run()时抛出异常，则调用此方法处理异常（设置最后状态，返回算执行成功还是算执行失败）
     * @param \ErrorException $e
     * @return boolean
     */
    public function onError(\ErrorException $e)
    {
        $this->lastMsg = "[Error]".$e->getMessage();
        error_log("Error_Crond_Task:".get_called_class()."#".$e->getMessage()."\n".$e->getTraceAsString());
        return false;
    }
}
