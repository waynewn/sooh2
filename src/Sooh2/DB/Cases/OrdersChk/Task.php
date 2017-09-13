<?php
namespace Sooh2\DB\Cases\OrdersChk;
/**
 * 业务资金对账
 * 主要负责数据导入部分的主流程
 * @author wangning
 */
abstract  class Task {
    /**
     *
     * @var AccountMirror 
     */
    protected $_accMirror;
    /**
     *
     * @var AccountMirror 
     */
    protected $_accConfirmed;
    /**
     *
     * @var BatchRecord
     */
    protected $_batchCur;
    /**
     *
     * @var BatchRecord
     */
    protected $_batchLastConfirmed;    
    /**
     *
     * @var Orders
     */
    protected $_orders;
    /**
     * 
     * @param int $ymd  yyyymmdd（批次或者说是执行日期）
     * @param array $users 需要指定检查的用户（非空数据时，跳过前面的全订单导入步骤）
     * @return type
     */
    public function chkStepAll($ymd=null,$users=array())
    {
        $this->step_prepare($ymd);//清理、设置准备环境
        if(empty($users)){
            echo "QSC：导入订单数据\n";
            $this->step_importOrders();//导入订单数据
            echo "QSC：根据导入的订单重置AccountMirror\n";
            $this->step_resetAccMirror();//根据导入的订单重置AccountMirror
            echo "QSC：检查所有用户的订单状态\n";
            $this->step_checkAllUser();//检查所有用户的订单状态
            echo "QSC：找出有错的用户，重新导入相关订单检查一下\n";
            $us = $this->_accMirror->userWithError();
            if(!empty($us)){//找出有错的用户，重新导入相关订单检查一下
                echo "QSC：发现". sizeof($us)."个错误用户\n";
                $this->step_recheckUsers($us);
            }
        
        }else{//直接重新导入相关订单检查一下
            echo "QSC：". sizeof($us)."个指定用户需要检查\n";
            $this->step_recheckUsers($users);
        }
        echo "QSC：生成报表\n";
        return $this->step_report();//更新批次状态
    }
    /**
     * 跟进导入订单，重置account mirror库表
     * （使用了mysql的 insert into xxx select xxx from xxx）
     */
    public function step_resetAccMirror()
    {
        list($db0,$tbOrders) = $this->_orders->dbAndTbName();
        list($db2,$tb2) = $this->_accMirror->dbAndTbName();
        $db2->exec(['delete from '.$tb2,
            'insert into '.$tb2.' select distinct(uid),-1,\'\',0,0,\'\',1 from '.$tbOrders]);
    }
    /**
     * 系统安装：需要安装以下库表
     */
    public static function install()
    {
        AccountMirror::install();
        AccountLastOk::install();
        BatchRecord::install();
        Orders::install();
    }
    /**
     * 第一步，清理、设置准备环境
     * @param int $ymd yyyymmdd准备进行那一天的对账
     */
    public function step_prepare($ymd=null)
    {
        if($this->_odrChkRet===null){
            $this->_odrChkRet = new UserOrdersRet;
        }
        $ymd-=0;
        $this->batchYmd = ($ymd<20001230 || $ymd>30001230)?date('Ymd'):$ymd;

        //检查是否是已经确认过的批次
        $this->step_prepare_batch($this->batchYmd);
        if($this->_batchCur->exists()){
            if($this->_batchCur->isBatchConfirmed()){
                throw new \ErrorException($this->batchYmd.' already confirmed');
            }else{
                if($this->batchYmd<$this->_batchLastConfirmed->getBatchId()){
                    throw new \ErrorException($this->batchYmd.' already confirmed');
                }else{
                    $this->_batchCur->resetForNewCheck();
                }
            }
        }else{
            $this->_batchCur->createNew();
        }
        $this->dtBatchFrom = $this->_batchLastConfirmed->getDtLast();
        
        //清理订单和用户表
        $this->_orders->startYmd($this->batchYmd);
        
        //设置订单检索的时间段范围
        
        //$this->dtBatchTo = strtotime($this->batchYmd)+86400-1;
    }
    /**
     * 
     * @param type $batchYmd
     * @throws \ErrorException
     */
    protected function step_prepare_batch($batchYmd)
    {
        $this->_batchCur = BatchRecord::getCopy($batchYmd);
        $this->_batchCur->load();
        $this->_batchLastConfirmed = BatchRecord::getLastConfirmedBatch();
        $this->_batchLastConfirmed->load();
    }
    /**
     *
     * @var OdrChkRet
     */
    protected $_odrChkRet=null;
    protected $batchYmd;
    protected $dtBatchFrom = 0;
    //protected $dtBatchTo = 9999999999;
    /**
     * 遍历所有的订单完成批次入库
     */
    public function step_importOrders()
    {
//        Orders::ensureOrderA($orderId, $type, $status, $amount, $extAmount1, $extAmount2, $walletChange);
//        Orders::ensureOrderB($orderId, $type, $status, $amount, $extAmount1, $extAmount2, $walletChange);
    }

    /**
     * 在account mirror表中找出没有比对过的
     * @param type $hasErrorOriginal
     */
    public function step_checkAllUser($hasErrorOriginal=-1)
    {
        $db = $this->_accMirror->dbWithTablename();
        $tb = $db->kvobjTable();
        do{
            $uid = $db->getOne($tb, 'uid',array('hasError'=>$hasErrorOriginal));
            if(empty($uid)){
                break;
            }
            $this->_orders->recheck($uid,$this->_odrChkRet);
        }while(true);
    }

    /**
     * 根据有错误的用户的列表,重新抓订单比对一次
     */
    public function step_recheckUsers($us)
    {
//        Orders::ensureOrderA($orderId, $type, $status, $amount, $extAmount1, $extAmount2, $walletChange);
//        Orders::ensureOrderB($orderId, $type, $status, $amount, $extAmount1, $extAmount2, $walletChange);
        foreach($us as $uid)
        {
            
        }
    }
    /**
     * 
     */
    
    /**
     * 记录比对结果（报告）并返回
     */
    public function step_report()
    {
        $batchRecord = $this->_batchCur;
        
        $users = array(
                'usrCount'=>$this->_accMirror->userCount($this->batchYmd)
                );

        list($db,$tb) = $this->_orders->dbAndTbName();
        $batchRecord->setRet($users,
                array(
                    'missing'=>$db->getRecordCount($tb, array(Orders::field_uid=>'',Orders::field_orderStatus.'1'=>OrderStatus::success)),
                    'unknownstatus'=>$db->getRecordCount($tb, array(Orders::field_orderStatus.'1'=>OrderStatus::unknown))
                    )
                );
        $batchRecord->setField('batchStatus', \Rpt\OrderCheck\Investor\BatchRecord::status_toBeConfirm);
    }
    /**
     * 确认批次(只能确认最新的)
     */
    public function confirm($ymd)
    {
        if(empty($this->_batchLastConfirmed)){
            throw new \ErrorException('confirm() needs prepare() called first');
        }
        if ($this->_batchLastConfirmed->getBatchId()>=$ymd){
            throw new \ErrorException('batch '.$ymd.' already confirmed');
        }
        if($ymd<$this->_batchLastConfirmed->getLastBatchYmd()){
            throw new \ErrorException('only newest catch can be confirm');
        }
        $this->_batchCur->load();
        if(!$this->_batchCur->exists()){
            throw new \ErrorException('batch '.$ymd.' not found');
        }else{
            $this->_batchCur->setField('batchStatus', BatchRecord::status_confirmed);
            $this->_batchCur->setField('dtLastOrder', $this->_orders->getLastTime($this->batchYmd));
            $this->_batchCur->saveToDB();
        }
        $db = $this->_orders->dbWithTablename();
        //有uid的，状态是终态的，标记confirmed
        $db->updRecords($db->kvobjTable(),array(Orders::field_confirmed=>2),array('!'.Orders::field_uid=>'',Orders::field_confirmed=>0,Orders::field_orderStatus.'1'=>array(OrderStatus::failed, OrderStatus::success, OrderStatus::refused)));
        $db->updRecords($db->kvobjTable(),array(Orders::field_confirmed=>1),array('!'.Orders::field_uid=>'',Orders::field_confirmed=>0,Orders::field_orderStatus.'1'=>array(OrderStatus::frose, OrderStatus::prepare, OrderStatus::unknown)));
        $this->copyAccountDataOnConfirm();
    }
    protected function copyAccountDataOnConfirm()
    {
        list($db,$tbMirror) = $this->_accMirror->dbAndTbName();

        list($db2,$tbConfirmed)=$this->_accConfirmed->dbAndTbName();
        $db2->exec(array("insert into ".$tbConfirmed.' select * from '.$tbMirror." where $tbMirror.hasError=0 "
            . ' ON DUPLICATE KEY UPDATE '
            . " $tbConfirmed.hasError=$tbMirror.hasError, $tbConfirmed.errors=$tbMirror.errors,$tbConfirmed.dtOrderLast=$tbMirror.dtOrderLast,$tbConfirmed.dtUpdate=$tbMirror.dtUpdate,$tbConfirmed.balance=$tbMirror.balance"));

        list($db3,$tbOrders) = $this->_orders->dbAndTbName();
        $db3->updRecords($tbOrders, array(Orders::field_preStatus.'='.Orders::field_orderStatus.'1'), array(Orders::field_orderStatus.'1'=> OrderStatus::frose));
    }
}
