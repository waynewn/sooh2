<?php
namespace Sooh2\DB\Cases\OrdersChk;

/**
 * 检查批次
 *
 * @author wangning
 */
class BatchRecord  extends \Sooh2\DB\KVObj{
    protected function onInit()
    {
        parent::onInit();
        $this->_tbName = 'tb_batchs_{i}';//表名的默认模板
    }   
    public function getBatchId()
    {
        return $this->_pkey['batchYmd'];
    }
    /**
     * 用于确认后续对账验收时，起点的时间
     */
    public function getDtLast()
    {
        if($this->exists()){
            return $this->getField('dtLastOrder')-60;
        }else{
            return 0;
        }
    }
    /**
     * 指定批次是否已经成功确认
     * @return bool
     */
    public function isBatchConfirmed()
    {
        if($this->exists()){
            return $this->getField('batchStatus')==self::status_confirmed;
        }else{
            return false;
        }
    }
    /**
     * 对于没确认的批次，准备重新检查
     */
    public function resetForNewCheck()
    {
        $this->setField('dtUpdate', time());
        $this->setField('batchStatus', self::status_checking);
        $this->saveToDB();
    }
    /**
     * 创建批次
     */
    public function createNew()
    {
        $this->setField('dtCreate', time());
        $this->setField('dtUpdate', time());
        $this->setField('smalllog', '');
        $this->setField('batchStatus', self::status_checking);
        $this->saveToDB();
    }
    const status_checking ='checking';
    const status_toBeConfirm = 'tbc';
    const status_confirmed = 'confirmed';
    const status_refused = 'refused';
    /**
     * 获取最近的已确认的批次
     * @return \Sooh2\DB\Cases\OrdersChk\BatchRecord
     */
    public static function getLastConfirmedBatch()
    {
        $db = static::getCopy(null)->dbWithTablename();
        $ymd = $db->getOne($db->kvobjTable(), 'batchYmd',array('batchStatus'=>self::status_confirmed),'rsort batchYmd');
        $o = static::getCopy($ymd);
        if($ymd){
            return $o->load();
        }else{
            return $o;
        }
    }
    public function getLastBatchYmd()
    {
        $db=$this->dbWithTablename();
        return $db->getOne($db->kvobjTable(),'max(batchYmd)');
    }
    public function setRet($userRet,$orderRet)
    {
        $this->setField('dtUpdate', time());
        $this->setField('batchStatus',self::status_toBeConfirm);

        $this->setField('smalllog', \Sooh2\Util::toJsonSimple(array_merge($userRet,$orderRet)));//包含是否有无主订单
        $this->saveToDB();
    }
    public static function install()
    {
        $db = static::getCopy(null)->dbWithTablename();
        $db->exec(array('create table if not exists '.$db->kvobjTable().'('
            . 'batchYmd int not null,'
            . 'dtCreate int not null default 0,'
            . 'dtUpdate int not null default 0,'
            . 'dtLastOrder int not null default 0,'
            . 'batchStatus varchar(64) not null default \''.self::status_checking.'\','
            . 'smalllog varchar(2000) not null default \'\','
            . 'rowVersion int not null default 0,'
            . 'primary key (batchYmd)'
            . ')'));
    }
}
