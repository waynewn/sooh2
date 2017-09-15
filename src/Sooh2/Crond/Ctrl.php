<?php
namespace Sooh2\Crond;
/**
 * 定时任务控制类 (如果默认的routeUri是__=apicopartner/hourly,那么定时任务中要加上__=apicopartner/hourly)
 * a) standalone目录下所有的任务都会被独立执行；
 * b) 其他的有依赖关系的任务要放在同一目录下（不能取名standalone）
 * c) 加载任务只扫描第一层目录，可以为任务建立更深的子目录
 * 
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Ctrl {
    /**
     * 手动调用还是定时任务自动调用的
     * @deprecated 改用 ::getInstance()
     * @return \Sooh2\Crond\Ctrl
     */
    public static function factory($runManually=false){ return self::getInstance($runManually); }
    protected static $_instance=null;
    /**
     * 手动调用还是定时任务自动调用的
     * @return \Sooh2\Crond\Ctrl
     */
    public static function getInstance($runManually=false){
        if(self::$_instance==null){
            self::$_instance = new Ctrl;
            self::$_instance->_isManual = $runManually;
        }
        return self::$_instance;
    }
    protected $_isManual=false;
    /**
     * 设置定时任务扫描目录
     * @return \Sooh2\Crond\Ctrl
     */
    public function initCrondDir($dir){$this->_baseDir=$dir;return $this; }
    protected $_baseDir;
    
    /**
     * 设置定时任务类的属于哪个命名空间
     * @return \Sooh2\Crond\Ctrl
     */
    public function initNamespace($dir){$this->_baseNamespace='\\'.trim($dir,'\\').'\\';return $this; }
    protected $_baseNamespace='';
    
    /**
     * 设置启动特定定时任务的命令的模板
     * @return \Sooh2\Crond\Ctrl
     */
    public function initCmdTpl($cmdTpl){$this->_cmdTpl=$cmdTpl;return $this; }
    protected $_cmdTpl;
    /**
     * 设置记录日志的控制类（）
     * @return \Sooh2\Crond\Ctrl
     */
    public function initLoger($loger){$this->_log=$loger;return $this; }
    /**
     *
     * @var \Sooh2\Crond\CrondLog
     */
    protected $_log=null;
    
    /**
     *
     * @var \Sooh2\Crond\Time 
     */
    protected $_dt;
    /**
     * 定时任务执行顺序
     * @var type 
     */
    protected $_planToRun=array();
    /**
     * 执行函数
     * @param string $task 从request中获取的task的值
     * @param int $ymdh  从request中获取的ymdh的值,表明是本次调用是作为什么时间运行的
     * @throws \ErrorException
     */
    public function runManually($task=null, $ymdh=null)
    {
        $this->_log->writeCrondLog(null, __FUNCTION__."($task=null, $ymdh=null)");
        clearstatcache();
        $this->_isManual=true;
        $this->_dt = \Sooh2\Crond\Time::getInstance();
        if($ymdh!==null){
            if(is_numeric($ymdh)){
                if($ymdh>99991231){//yyyymmddhh
                        $this->_dt->mktime($ymdh%100, 0, 0, floor($ymdh/100));
                }elseif($ymdh<19000101){
                        throw new \ErrorException('ymdh specified error:'.$ymdh);
                }else{//yyyymmdd
                        $this->_dt->mktime(0, 0, 0, $ymdh);
                }
            }else{
                $tmp = strtotime($ymdh);
                if($tmp===false){
                    throw new \ErrorException('ymdh specified error:'.$ymdh);
                }else{
                    $this->_dt->mktime(date('H',$tmp), date('i',$tmp), 0, date('Ymd',$tmp));
                }
            }
        }

        if(empty($task)){
                throw new \ErrorException('you need specify a task');
        }
        $dir=explode('.',$task);
        if(sizeof($dir)!==2){
            throw new \ErrorException('task specified error:'.$task);
        }
        
        if($dir[1]=='*'){
            $tasks = $this->getTasks($dir[0]);
            foreach($tasks as $task=>$fullpath){
                    $this->newTaskObj($dir[0],$task,$fullpath);
            }
        }else{
            $this->newTaskObj($dir[0],$dir[1],$this->_baseDir.'/'.  implode('/', $dir).'.php');
        }
        $this->loop($this->_dt);
    }
    /**
     * 
     * 自动调用的情况下，如果是在凌晨3点，执行一次清理执行日志
     * @param type $task
     * @param type $ymdh
     */
    public function runCrond($task=null, $ymdh=null)
    {
            $this->_log->writeCrondLog(null, __FUNCTION__."(task=$task, crond)");
            clearstatcache();
            $this->_isManual=false;
            $this->_dt = \Sooh2\Crond\Time::getInstance();
            if($this->_dt->hour==3){
                $this->_log->removeCrondLogExpired();
            }

            if(empty($task)){// loop and check all new paths in a hour
                    $this->runCrond_loopAll();
            }elseif(substr($task,-1)=='*'){// loop and check all new class in specify path in a hour
                    $dir = substr($task,0,-2);
                    $this->runCrond_loopOneDir($dir);
            }else{
                    $r = explode('.', $task);
                    $this->newTaskObj($r[0],$r[1],$this->_baseDir.'/'.$r[0].'/'.$r[1].'.php');
                    for($i=0;$i<60;$i++){
                            if($this->loop($this->_dt)){
                                    //$this->_log->writeCrondLog(null, __FUNCTION__." run single,needs sleep to ".$dt->hour.":".($dt->minute+1));
                                    $this->_dt->sleepTo($this->_dt->hour, $this->_dt->minute+1,0);
                            }else{
                                    //$this->_log->writeCrondLog(null, __FUNCTION__." run single,end no more");
                                    break;
                            }
                    }
            }

            //$this->_log->writeCrondLog(null, __FUNCTION__." done");
    }
    private function runCrond_loopAll()
    {
        $subdirs0 = array();
        for($i=0;$i<6;$i++){
                $subdirs = $this->getTaskDirs();
                //$this->_log->writeCrondLog(null, __FUNCTION__." scan subdirs,".sizeof($subdirs).' found');
                foreach ($subdirs as $subdir){
                        if(!in_array($subdir, $subdirs0)){
                                $this->forkTaskProcess($subdir.'.*');
                                $subdirs0[]=$subdir;
                        }
                }
                if($i<5){
                        sleep(600);
                        clearstatcache();
                }
        }
    }
    private function runCrond_loopOneDir($dir)
    {
        $task0=array();
        if($dir=='Standalone'){
            for($i=0;$i<6;$i++){
                $tasks = $this->getTasks($dir);
                $this->_log->writeCrondLog(null, __FUNCTION__." scan Standalone,".sizeof($tasks).' tasks found');
                foreach($tasks as $task=>$fullpath){
                        if(!in_array($task, $task0)){
                                $task0[]=$task;
                                $this->forkTaskProcess('Standalone.'.$task);
                        }
                }
                if($i<5){
                        sleep(600);
                        clearstatcache();
                }
            }
        }else{
            for($i=0;$i<60;$i++){
                if($i%10==0){
                    $tasks = $this->getTasks($dir);
                    $this->_log->writeCrondLog(null, __FUNCTION__." scan $dir,".sizeof($tasks).' tasks found');
                    foreach($tasks as $task=>$fullpath){
                        if(!in_array($task, $task0)){
                            $task0[]=$task;
                            $this->newTaskObj($dir,$task,$fullpath);
                        }
                    }
                    clearstatcache();
                }
                $ret = $this->loop($this->_dt);
                if($i>50&&$ret==false){
                    break;
                }
                $this->_dt->sleepTo($this->_dt->hour, $this->_dt->minute+1,0);
            }
        }
    }
    protected $_tasks=array();
    /**
     * @param \Sooh2\Crond\Time $dt 
     * @param \Sooh2\Crond\Task $_ignore_
     * @return boolean has task(s) needs to be run next loop
     */
    protected function loop($dt,$_ignore_=null)
    {
        foreach($this->_planToRun as $i=>$taskname){
            $_ignore_ = $this->_tasks[$taskname];
            try{
                $ret = $_ignore_->run($dt);
                //$this->_log->writeCrondLog($taskindex, "trace while($retry exec:$exec done:".sizeof($done)." bool:$numBool total:$total):ret:$taskindex=".  var_export($ret,true).' lastMsg='.$_ignore_->lastMsg. ' '. ($_ignore_->toBeContinue?'continue':'done'));
            } catch (\ErrorException $e) {
                $ret = $_ignore_->onError($e);
                $this->_log->writeCrondError($taskname, "error found(".$e->getMessage().") :ret=".  var_export($ret,true).' lastMsg='.$_ignore_->lastMsg. ' '. ($_ignore_->toBeContinue?'continue':'done'));
            }
            if($ret!==false || $_ignore_->lastMsg!==null){
                    $this->_log->updCrondStatus($dt->YmdFull, $dt->hour, $_ignore_->subdir.'.'.$taskname, $_ignore_->lastMsg, $ret?1:0, $this->_isManual?1:0);
            }

            if($_ignore_->toBeContinue==false){
                $_ignore_->free ();
                unset($this->_tasks[$taskname],$this->_planToRun[$i]);
            }
        }
        return true;
    }
    protected function forkTaskProcess($task)
    {
        $cmd = str_replace(array('{task}','{ymdh}'), array($task,date('YmdH',$this->_dt->timestamp())), $this->_cmdTpl);
        \Sooh2\Util::runBackground($cmd);
        $this->_log->writeCrondLog(null, __FUNCTION__."($task) with cmd=$cmd");
    }

    /**
     * 
     * @param string $taskname
     * @param string $fullpatch
     * @param \Sooh\Base\Crond\Task $_ignore_
     */
    protected function newTaskObj($subdir,$taskname,$fullpatch,$_ignore_=null)
    {
        $this->_log->writeCrondLog(null,__FUNCTION__."($subdir,$taskname,$fullpatch)");
        if(!isset($this->_tasks[$taskname])){
            include $fullpatch;

            $realclass = $this->_baseNamespace.$subdir.'\\'.$taskname;

            if(class_exists($realclass,false)){
                //$this->_log->writeCrondLog(null,__FUNCTION__."new $realclass() from $fullpatch");
                $_ignore_ = new $realclass($this->_isManual);
                //$this->_log->writeCrondLog(null,__FUNCTION__."new $realclass() ->init()");
                $_ignore_->init();
                $_ignore_->subdir=$subdir;
                $this->_log->writeCrondLog(null,__FUNCTION__."new $realclass() created");
                $this->_tasks[$taskname]=$_ignore_;
                $tmp = $_ignore_->execOrder();
                if($tmp<=0){
                    if(sizeof($this->_planToRun)>0){
                        $tmp = min($this->_planToRun)-1;
                    }

                    if(empty($tmp) || $tmp>=0){
                        $tmp=-1;
                    }

                    $this->_planToRun[$tmp] = $taskname;
                }else{
                    if(isset($this->_planToRun[$tmp])){
                        $this->_log->writeCrondLog(null,__FUNCTION__.' in '.$subdir.' found same exec-order:'.$taskname);
                    }else{
                        $this->_planToRun[$tmp] = $taskname;
                    }
                }
                $this->_log->updCrondStatus($this->_dt->YmdFull, $this->_dt->hour, $subdir.'.'.$taskname, 'inited',0, $this->_isManual?1:0);
            }else{
                $this->_log->writeCrondError(null,__FUNCTION__." $realclass not found, error classname or pathname or namespace");
            }
        }
        ksort($this->_planToRun);
    }
    protected function getTaskDirs()
    {
            $subdirs=array();
            $dh = opendir($this->_baseDir);
            if(!$dh){
                die($this->_log->writeCrondLog(null, "read base dir failed:".$this->_baseDir));
            }
            while(false!==($subdir=  readdir($dh))){
                    if($subdir[0]!=='.' && is_dir($this->_baseDir.'/'.$subdir)){
                            $subdirs[]=$subdir;
                    }
            }
            closedir($dh);
            sort($subdirs);
            return $subdirs;
    }
    protected function getTasks($path)
    {
            $classes=array();

            $dh = opendir($this->_baseDir.'/'.$path);
            if(!$dh){
                die($this->_log->writeCrondLog(null, "read tasks in dir failed:".$this->_baseDir.'/'.$path));
            }
            while(false!==($f=  readdir($dh))){
                    if(substr($f,-4)=='.php'){
                            $classes[substr($f,0,-4)]=$this->_baseDir.'/'.$path.'/'.$f;
                    }
            }
            closedir($dh);
            return $classes;
    }
}
