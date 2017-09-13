<?php
namespace Sooh2\DB\Cases;

/**
 * 流水账:
 *     
 * @author simon.wang
 */
class AccountLog extends \Sooh2\DB\KVObj{
    /**
     * 余额最小值，任何订单都会检查
     * @var int
     */
    protected $minBalance=0;
    const status_new = -1;
    const status_ok = 0;
    const status_rollback=1;
    const status_timeout=2;
    /**
     *
     * @var \Sooh2\Db\Interfaces\DB 
     */
    protected $__db;
    protected $__tb;
    /**
     * 根据流水记录算出的余额（不包含被事务锁定的）
     */
    public function getBalance()
    {
        return $this->getField('balance');
    }
    /**
     * 获取帐户类型
     * @return string
     */
    public function getAccountType()
    {
        return 'default';
    }
    /**
     * 获取流水记录
     * @param array $where 参数给出过滤条件，比如： array(alStatus=>0)  只要成功的记录
     * @param mixed $pagesize 获取最近的多少条记录 或 pager 类
     * @return array
     */
    public function getHistory($where=array(),$pagesize=30)
    {
        if(is_numeric($pagesize)){
            $pager = new \Sooh2\DB\Pager($pagesize);
            $pager->init(-1,1);
        }else{
            $pager = $pagesize;
        }
        unset($where['alUserId']);
        unset($where['=alUserId']);
                
        $db = $this->dbWithTablename();
        return $db->getRecords($db->kvobjTable(), '*',
                array_merge(array('alUserId'=>$this->_pkey['alUserId']),$where),
                'rsort alRecordId',
                $pager->page_size,$pager->rsFrom());
    }
    
    public static function getTheOne($userId,$recordid)
    {
        return parent::getCopy(array('alUserId'=>$userId,'alRecordId'=>$recordid));
    }
    /**
     * 获取用户最近的成功的记录（可能后面有个进行中的任务，所以此时的余额不是绝对准确）
     * @param type $userId
     * @return \Sooh2\DB\Cases\AccountLog
     */
    public static function getRecentCopy($userId)
    {
        $ret = parent::getCopy(array('alUserId'=>$userId,'alRecordId'=>0));
        $ret->__db  =$ret->dbWithTablename();
        $ret->__tb = $ret->__db->kvobjTable();
        return $ret;
    }
    public static function getCopy($pkey) {
        throw new \ErrorException('use getRecentCopy() instead');
    }
    protected function throwErrorUnfinishOrderFound()
    {
        throw new \ErrorException('unfinish orders found');
    }
    protected function throwErrorCreateOrder()
    {
        throw new \ErrorException('create orders failed');
    }
    protected function throwErrorMinBalance()
    {
        throw new \ErrorException('try reduce balance lower than '.$this->minBalance);
    }
    public function saveToDB($func_update = null, $maxRetry = 3) {
        throw new \ErrorException('use transactionXXXXX() instead');
    }
    protected function reload() {
        $r = $this->__db->getRecord($this->__tb, '*',array('alUserId'=>$this->_pkey['alUserId'],'alStatus'=>self::status_ok),'rsort alRecordId');

        $this->fillStruct($r);
        return $this->_pkey;
    }
    /**
     * 用读出的记录填充kvobj结构
     * @param type $r
     */
    protected function fillStruct($r)
    {
        if(empty($r)){//第一次，没以前的记录
            $this->_pkey['alRecordId'] = 0;
            $this->r=array(
                'alStatus'=>0,'chg'=>0,'balance'=>0,'rowVersion'=>0,'ymd'=>0
                );

        }else{
            unset($r['alUserId']);
            $this->_pkey['alRecordId'] = $r['alRecordId'];

            unset($r['alRecordId']);
            foreach($r as $k=>$v){
                $this->r[$k]= $v;
            }
        }
    }
    public function transactionDirectly($change,$orderType,$orderId,$arrExtraFields=null)
    {
        if($arrExtraFields===null){
            $arrExtraFields=array('alStatus'=>self::status_ok);
        }elseif(is_array($arrExtraFields)){
            $arrExtraFields['alStatus'] = self::status_ok;
        }else{
            throw new \ErrorException('arrExtraFields should be array');
        }
        return $this->transaction_new($change, $orderType, $orderId, $arrExtraFields);
    }
    
    /**
     * 创建一条流水,成功返回该用户记录流水id
     * @param string $userId 用户id
     * @param int $change  正负标示加减金额
     * @param string $orderType 对应订单类型
     * @param string $orderId 对应交易订单号
     * @param array $arrExtraFields 自定义扩展字段（比如设计上要求记录购买订单对应的产品id）
     * @throws \ErrorException
     * @return string uid_recordId 
     */
    public function transactionStart($change,$orderType,$orderId,$arrExtraFields=null)
    {
        if($arrExtraFields===null){
            $arrExtraFields=array('alStatus'=>self::status_new);
        }elseif(is_array($arrExtraFields)){
            $arrExtraFields['alStatus'] = self::status_new;
        }else{
            throw new \ErrorException('arrExtraFields should be array');
        }
        return $this->transaction_new($change, $orderType, $orderId, $arrExtraFields);
    }
    protected function transaction_new($change,$orderType,$orderId,$arrExtraFields=null)
    {
        $fields = array('alUserId'=>$this->_pkey['alUserId'],'alRecordId'=>1);
        $fields['alOrderType'] = $orderType;
        $fields['alOrderId'] = $orderId;
        $fields['chg'] = $change;
        $fields['balance']=-1;
        $dt = time();
        $fields['ymd']=date('Ymd',$dt);
        $fields['dtCreate']=$dt;
        foreach($arrExtraFields as $k=>$v){
            $fields[$k] = $v;
        }

        $fields['rowVersion'] = 1;
        
        $retry = 10;
        $where = array('alUserId'=>$this->_pkey['alUserId']);
        $lastRecord = $this->__db->getRecord($this->__tb , '*',$where,'rsort alRecordId');//取出最近的一条记录
        while($retry){
            $retry--;

            if(is_array($lastRecord)){
                if($fields['alRecordId']==1){//新增记录应该使用的alRecordId
                    $fields['alRecordId'] = $lastRecord['alRecordId']+1;
                }
                if($lastRecord['alStatus']==self::status_new){//如果是新建的，等5毫秒再试试
                    usleep(5);
                    $lastRecord = $this->__db->getRecord($this->__tb , '*',$where,'rsort alRecordId');
                    continue;
                }elseif($lastRecord['alStatus']!=self::status_ok){//如果是失败的记录，再往前读一条记录
                    $lastRecord = $this->__db->getRecord($this->__tb , '*',array_merge($where,array('<alRecordId'=>$lastRecord['alRecordId'])),'rsort alRecordId');
                    continue;
                }
                $fields['balance'] = $lastRecord['balance']+$change;
                if($fields['balance']<$this->minBalance || !is_numeric($change)){
                    $this->throwErrorMinBalance();
                }
            }else{
                $fields['balance'] = $change;
            }
            
            try{
                $this->__db->addRecord($this->__tb, $fields);
                $this->fillStruct($fields);
                return $fields['alUserId'].'_'.$fields['alRecordId'];
            } catch (\Sooh2\DB\DBErr $ex) {
                if($ex->keyDuplicated=='alRecordId'){//alRecordId重复，重头开始
                    $fields['alRecordId']=1;
                    $lastRecord = $this->__db->getRecord($this->__tb , '*',$where,'rsort alRecordId');
                }else{
                    throw $ex;
                }
            }
            
        }
        \Sooh2\Misc\Loger::getInstance()->app_warning($fields,'add records failed after retry, lastRecord status='.$lastRecord['alStatus']);
        if($lastRecord['alStatus']==-1){
            $this->throwErrorUnfinishOrderFound();
        }else{
            $this->throwErrorCreateOrder();
        }
    }
    /**
     * 事务成功完成
     * 考虑到资金流水不会放redis之类的，updRecord应该都能返回变更记录数量，所以，判断使用的===1
     * @return boolean
     */
    public function transactionCommit()
    {
        $where= $this->_pkey;
        //$where['rowVersion']=$this->r['rowVersion'];
        $ret = $this->__db->updRecords($this->__tb , array('alStatus'=>self::status_ok,'rowVersion'=>$this->r['rowVersion']+1),$where,'rsort alRecordId');
    
        if($ret===1){
            return true;
        }else {
            return false;
        }
    }

    public function transactionRollback()
    {
        $where= $this->_pkey;
        //$where['rowVersion']=$this->r['rowVersion'];
        $ret = $this->__db->updRecords($this->__tb , array('alStatus'=>self::status_rollback,'rowVersion'=>$this->r['rowVersion']+1),$where,'rsort alRecordId');
    
        if($ret===1){
            return true;
        }else {
            return false;
        }
        
    }
    


}

