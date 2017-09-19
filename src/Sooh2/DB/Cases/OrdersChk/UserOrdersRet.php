<?php
namespace Sooh2\DB\Cases\OrdersChk;

/**
 * 一个批次里，用户的所有的订单的统计结果(要带截至时间，要找个合适的时间作为截至时间)
 * 
 *
 * @author wangning
 */
class UserOrdersRet {
    /**
     * 
     * @param string $uid 哪个用户的
     */
    public function __construct() {

    }
    protected $_uid;
    protected $_dtAfter=0;//该用户上次成功对账，截至的订单时间

    /**
     * 记录发现的错误订单的情况
     * @param type $orderId
     * @param type $errorDesc
     * @param type $orderType
     */
    public function markErrorOrders($orderId,$errorDesc,$orderType='ignore')
    {
        $this->_errorOrders[] = array('ordersId'=>$orderId,'ordersError'=>$errorDesc,'ordersType'=>$orderType);
    }
    protected $_errorCommon=array();
    protected $_errorOrders=array();

    protected $_balance=array();

    public function reset($uid)
    {
        $this->_uid = $uid;
        $tmp= $this->getAccLastokByUid();
        if($tmp->exists()){
            $this->_dtAfter=$tmp->getField('dtOrderLast');
            $this->_balance=$tmp->getField('balance');
        }else{
            $this->_dtAfter=0;
            $this->_balance=array();
        }
        AccountLastOk::freeCopy($tmp);
        $this->_errorCommon=array();
        $this->_errorOrders=array();
        $this->_newLastOrderTimestamp=0;
    }
    public function getUid()
    {
        return $this->_uid;
    }
    /**
     * 发生异常的时候的处理函数，信息立即入库
     * @param \ErrorException $e
     */
    public function appendErrorException($e)
    {
        $msg = $e->getMessage();
        $this->_errorCommon[]=$msg;
    }
    /**
     * 检查某个批次下用户所有的订单,计算检查余额
     * （如果不支持计算金额的那个sql，这个方法需要替换）
     * @param \Sooh2\DB\Interfaces\db $dbOfOrders
     * @param type $uid
     * @param type $batchId
     */
    public function chkUserOrders($dbOfOrders,$batchYmd)
    {
        $uid = $this->getUid();
        
        $tb= $dbOfOrders->kvobjTable();
        //报告错误订单
        $rs = $dbOfOrders->getRecords($tb,array(Orders::field_orderId,Orders::field_orderDesc,Orders::field_orderType,Orders::field_err),
                array(Orders::field_batchYmd=>$batchYmd,Orders::field_uid=>$uid,'!'.Orders::field_err=>Orders::err_none,));//,'['.Orders::field_dt=>$this->dtBatchTo
        foreach($rs as $r){
            $this->markErrorOrders($r[Orders::field_orderId], $r[Orders::field_orderDesc], $r['orderType']);
        }

        //计算金额变化
        $this->_newLastOrderTimestamp = 0;
        $statusField=Orders::field_orderStatus.'1';
        $change = $this->chkUserOrders_balanceChg(
                $dbOfOrders->getRecords($tb,'orderType,prestatus,'.$statusField.' as orderStatus,sum(orderAmount1) as orderAmount,sum(payAmount1) as payAmount, sum(feeAmount1) as feeAmount,max(dt) as dt',
                                        array(Orders::field_batchYmd=>$batchYmd,Orders::field_uid=>$uid,Orders::field_err=>Orders::err_none,
                                            $statusField=>array(OrderStatus::success,OrderStatus::frose),'>'.Orders::field_dt=>$this->_dtAfter),
                                        'group orderType groupby prestatus groupby '.$statusField
                ));
        
        foreach($change as $k=>$v){
            $this->_balance[$k]+=$v;
        }

    }
    protected $_newLastOrderTimestamp;
    /**
     * 计算余额，发现错误就记录错误
     * @param type $rs
     * @return type
     */
    protected function chkUserOrders_balanceChg($rs)
    {
        $change = array();
//        foreach($rs as $r){
//            if($r['dt']>$this->_newLastOrderTimestamp){
//                $this->_newLastOrderTimestamp = $r['dt'];
//            }
//            if($r['orderStatus']== OrderStatus::frose){//进入冻结状态的话
//                if($r['payAmount']>0){
//                    $change['balance']-=$r['payAmount'];
//                    $change['frose']+=$r['payAmount'];
//                }else{
//                    $change['balance']-=$r['orderAmount'];
//                    $change['frose']+=$r['orderAmount'];
//                }
//            }else{
//                switch(strtolower($r['orderType'])){
//                    case 'buy':$change['balance']-=$r['payAmount'];break;//购买（确认）
//                    case 'unbuy':$change['balance']+=$r['orderAmount']-$r['feeAmount'];break;//赎回
//                    case 'interest':$change['balance']-=$r['payAmount'];break;//收益回款
//                    case 'withdraw':$change['balance']-=$r['payAmount'];break;//提现
//                    case 'recharge':$change['balance']+=$r['payAmount'];break;//充值
//
//                }
//            }
//        }
        return $change;
    }
    protected function getAccLastokByUid()
    {
        $accMirror = AccountLastOk::getCopy($this->_uid);
        $accMirror->load();
        return $accMirror;
    }
    protected function getAccMirrorByUid()
    {
        $accMirror = AccountMirror::getCopy($this->_uid);
        $accMirror->load();
        return $accMirror;
    }
    public function updUser()
    {
        $accMirror = $this->getAccMirrorByUid();
        $errOrders = sizeof($this->_errorOrders);
        $accMirror->setField('hasError', sizeof($this->_errorCommon)+ $errOrders);
        if($errOrders>3){
            $str = \Sooh2\Util::toJsonSimple(array_merge($this->_errorCommon,array('ordersId'=>'too_many','ordersError'=>'超过10个错误订单','ordersType'=>'ignore')));
        }else{
            $str = \Sooh2\Util::toJsonSimple(array_merge($this->_errorCommon,$this->_errorOrders));
        }

        $accMirror->setField('errors', $str);
        $accMirror->setField('dtOrderLast', $this->_newLastOrderTimestamp);
        $accMirror->setField('dtUpdate', time());
        $accMirror->setField('balance', $this->_balance);
        try{
            $accMirror->saveToDB();
        } catch (\ErrorException $ex) {
            \Sooh2\Misc\Loger::getInstance()->app_warning('对账时记录用户'.$this->_uid.'失败（'.$ex->getMessage().'）'.json_encode( $accMirror->dump()  ));
        }
        $this->freeAccMirror($accMirror);
    }
    protected function freeAccMirror($obj)
    {
        
    }
}
