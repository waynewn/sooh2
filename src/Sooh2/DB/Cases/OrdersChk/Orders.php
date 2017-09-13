<?php
namespace Sooh2\DB\Cases\OrdersChk;

/**
 * 订单情况
 * 注意：因订单时间是用两个来源中最新的为主，所以如果服务器时间同步误差太大，会引发其他问题
 * 注意：这里的订单记录，与批次什么的无关，单纯的比对用的
 * @author wangning
 */
class Orders extends \Sooh2\DB\KVObj{
    protected function onInit()
    {
        parent::onInit();
        $this->_tbName = 'tb_orders_{i}';//表名的默认模板
    }    
    /**
     * 插入一条待对账记录的其中的一个来源，两个来源的status要翻译成统一的
     */
    public function ensureOrderA($uid,$orderId,$type,$dt,$status,$intro,$orderAmount,$payAmount,$feeAmount)
    {
        $this->ensureOrder('1', $uid,$orderId, $type, $dt, $status, $intro,$orderAmount,$payAmount,$feeAmount);
    }
    /**
     * 插入一条待对账记录的其中的一个来源，两个来源的status要翻译成统一的
     */
    public function ensureOrderB($uid,$orderId,$type,$dt,$status,$intro,$orderAmount,$payAmount,$feeAmount)
    {
        $this->ensureOrder('2', $uid,$orderId, $type, $dt, $status,$intro, $orderAmount,$payAmount,$feeAmount);
    }
    /**
     * 
     * @param type $AB 订单来源(1或2)
     * @param type $uid uid
     * @param type $orderId 订单id
     * @param type $type  订单类型
     * @param type $dt    订单时间（以两个源中大的为准）
     * @param type $status 
     * @param type $intro
     * @param type $orderAmount
     * @param type $payAmount
     * @param type $feeAmount
     */
    protected function ensureOrder($AB,$uid,$orderId,$type,$dt,$status,$intro,$orderAmount,$payAmount,$feeAmount)
    {
        list($db,$tb) = $this->dbAndTbName();
        $fields = array(
            self::field_orderType =>$type,
            self::field_orderDesc=>$intro,
            self::field_batchYmd=>$this->batchYmd,
            self::field_err=>self::err_default,
            self::field_dt=>$dt,
            self::field_orderStatus.$AB=>$status,
            self::field_orderAmount.$AB=>$orderAmount,
            self::field_feeAmount.$AB=>$feeAmount,
            self::field_payAmount.$AB=>$payAmount,
            self::field_confirmed=>0,
        );
        $pkey = array(self::field_uid=>empty($uid)?'':$uid,self::field_orderId=>$orderId,);
        $r = $db->getRecord($tb, self::field_confirmed,$pkey);
        if(!empty($r)){
            if($r[self::field_confirmed]==2){
                return;
            }else{
                $db->updRecords($tb, $fields, $pkey);
            }
        }else{
            $db->addRecord($tb, $fields,$pkey);
        }
    }

    const field_orderId = 'orderId';
    const field_uid = 'uid';
    const field_orderType = 'orderType';
    const field_orderDesc = 'orderDesc';
    const field_dt = 'dt';
    const field_err = 'err';
    const field_confirmed='confirmed';//0 没确认， 1 非终态确认过  2 终态确认
    const err_default = 'unknown';
    const err_none='none';
    const field_batchYmd='batchYmd';
    const field_orderStatus = 'status';
    const field_orderAmount = 'orderAmount';
    const field_feeAmount = 'feeAmount';
    const field_payAmount = 'payAmount';
    const field_preStatus = 'prestatus';
    public function getLastTime($batchYmd)
    {
        list($db,$tb)=$this->dbAndTbName();
        return $db->getOne($tb,'dt',array('batchYmd'=>$batchYmd),'rsort dt');
    }
    public static function install()
    {
        $db = static::getCopy(null)->dbWithTablename();
        $db->exec(array('create table if not exists '.$db->kvobjTable().'('
            . 'orderId varchar(64) not null default \'unknown\','
            . 'uid varchar(64) not null default \'unknown\','
            . 'batchYmd int not null,'
            . 'dt int not null default 0,'
            . 'orderType varchar(32) not null default \'unknown\','
            . 'orderDesc varchar(90) not null default \'unknown\','
            . 'err varchar(90) not null default \''.self::err_default.'\','
            . 'prestatus  varchar(36) not null default \''.OrderStatus::unknown.'\',' 
            . 'status1 varchar(36) not null default \''.OrderStatus::unknown.'\','
            . 'status2 varchar(36) not null default \''.OrderStatus::unknown.'\','
            . 'orderAmount1 bigint not null default 0,'
            . 'orderAmount2 bigint not null default 0,'
            . 'feeAmount1 bigint not null default 0,'
            . 'feeAmount2 bigint not null default 0,'
            . 'payAmount1 bigint not null default 0,'
            . 'payAmount2 bigint not null default 0,'
            . 'confirmed int not null default 0,'
            . 'rowVersion int not null default 0,'
            . 'primary key (uid,orderId),'
            . 'index i (batchYmd,uid,err)'
            . ')'));
    }
    
    /**
     * 准备对某一天之前的订单
     * @param int $ymd
     */
    public function startYmd($ymd)
    {
        $this->maxDt = strtotime($ymd)+86400-1;
        $this->batchYmd = $ymd;
    }

    
    protected $batchYmd;
    protected $maxDt;//对账日期当天的23:59:59
    /**
     * 开始校验某个批次或某些用户的记录
     * 以用户为单位，每对完一个用户的，回调通知一次，设置用户状态
     * @param mixed $uid
     * @param \Sooh2\DB\Cases\OrdersChk\UserOrdersRet $ret
     */
    public function recheck($uid,$ret)
    {
        //echo "QSC：start recheck".$uid."\n";
        list($db,$tb) = $this->dbAndTbName();
        try{
            $ret->reset($uid);
            //echo "QSC：recheck status".$uid."\n";
            $this->chkOrdersStatusAndAmount($db, $tb,$uid);
            //echo "QSC：recheck recalc".$uid."\n";
            $db->kvobjTable($tb);
            $ret->chkUserOrders($db,$this->batchYmd);
        } catch (\ErrorException $e){
            echo "QSC：recheck err".$uid.'#'.$e->getMessage()."\n".$e->getTraceAsString();
            $ret->appendErrorException($e);
        }
        $ret->updUser();
  
    }
    /**
     * 检查订单状态和金额
     * @param type $dbOfOrders
     * @param type $tb
     * @param type $uid
     * @return type
     */
    protected function chkOrdersStatusAndAmount($dbOfOrders,$tb,$uid)
    {
        //检查订单
        $where0 = array(self::field_batchYmd=>$this->batchYmd,self::field_uid=>$uid,self::field_err=>self::err_default);
        $failed = array(OrderStatus::failed, OrderStatus::refused, OrderStatus::prepare);
        //处理掉允许缺失的情况
        $dbOfOrders->updRecords($tb, array(self::field_err=>self::err_none), 
                                    array_merge($where0,array(self::field_orderStatus.'1'=> OrderStatus::unknown,self::field_orderStatus."2"=>$failed)));
        $dbOfOrders->updRecords($tb, array(self::field_err=>self::err_none), 
                                    array_merge($where0,array(self::field_orderStatus.'1'=>$failed,self::field_orderStatus."2"=>OrderStatus::unknown)));
        //订单状态不一致
        $dbOfOrders->updRecords($tb, array(self::field_err=>'订单状态不匹配'), 
                                    array_merge($where0,array(self::field_orderStatus.'1<>'.self::field_orderStatus."2")));
        //各种金额不一致
        $dbOfOrders->updRecords($tb, array(self::field_err=>'订单金额不匹配'),
                                    array_merge($where0,array(self::field_orderAmount."1<>".self::field_orderAmount."2")));
        
        $dbOfOrders->updRecords($tb, array(self::field_err=>'手续费金额不匹配'),
                                    array_merge($where0,array(self::field_feeAmount."1<>".self::field_feeAmount."2")));

        $dbOfOrders->updRecords($tb, array(self::field_err=>'实付金额不匹配'),
                                    array_merge($where0,array(self::field_payAmount."1<>".self::field_payAmount."2")));
        //设置剩余订单比对结果为正常
        $dbOfOrders->updRecords($tb, array(self::field_err=>self::err_none), $where0);
    }

}
