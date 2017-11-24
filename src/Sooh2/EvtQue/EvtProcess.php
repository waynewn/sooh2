<?php


namespace Sooh2\EvtQue;

/**
 * 事件处理类（高频任务，目前空闲时会sleep 150ms)，子类需要写两个函数 init 和 onEvt
 * 
 * loopHourly 是入口，处理流程是：
 * 
 * 1.每小时启动一次，
 * 2.通过QueData::getOne()获取一个未认领的任务,并标记为被当前进程接手处理中
 * 3.找到对应任务的EvtProcess子类，实例化并执行，
 * 4.执行结束后: QueDataLog::createNew 记录结果到日志表;QueDat删除任务
 * 5.如果还在当前小时内，找下一事件任务
 *  
 * @author simon.wang
 */
class EvtProcess {
    public static $msSleep=150;
    /**
     *
     * @var \Sooh2\EvtQue\EvtData 
     */
    protected $evtData;
    protected static $_instances=array();
    /**
     * 
     * @param string $_preNameSpace 实现实际处理任务的EvtProcess子类的命名空间的前面的部分（拼上evtid就是完整类名）
     * @param string $evtDataClass QueData或其子类的类名（用到里面的getOne和endJob方法）
     */
    public static function loopHourly($_preNameSpace='\\Prj\\Events\\',$evtDataClass='\\Sooh2\\EvtQue\\QueData')
    {
        $loger = \Sooh2\Misc\Loger::getInstance();
        $hour = date('YmdH');
        while(date('YmdH')==$hour){
            $evt = $evtDataClass::getOne();
            if($evt){
                
                $evtData = $evt->getEvtData();
                $evtCtrl = $_preNameSpace.($evtData->evtId);
                try{
                    if(class_exists($evtCtrl, true)){
                        $ret = $evtCtrl::getInstance()->init($evtData)->onEvt();
                        $evt->endJob($ret);
                    }else{
                        $loger->app_warning('事件处理类'.$evtCtrl.'没有找到 ' . $evtDataClass::$pid);
                    }
                }catch(\ErrorException $e){
                    $loger->app_warning('事件处理类'.$evtCtrl.'报错:'.$e->getMessage() .' ' . $evtDataClass::$pid);
                }
            }else{
                usleep(static::$msSleep);
            }
        }
    }
    

    /**
     * 
     * @return \Sooh2\EvtQue\EvtProcess
     */
    public static function getInstance()
    {
        $c = get_called_class();
        if(!isset(self::$_instances[$c])){
            self::$_instances[$c] = new $c;
        }
        return self::$_instances[$c];
    }
    public function init($evtData)
    {
        $this->evtData=$evtData;
        return $this;
    }
    public function onEvt()
    {
        
    }
}
