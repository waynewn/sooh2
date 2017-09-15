<?php
namespace Sooh2\Crond;
/**
 * 记录计划任务日志（创建类的时候自动检查数据库表是否存在）
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class CrondLog extends \Sooh2\DB\KVObj{
    /**
     * 
     * @return \Sooh2\Crond\CrondLog
     */
    public function disableTraceLog()
    {
        $this->_flgTrace=false;
        return $this;
    }
    protected $_flgTrace=true;
    /**
     * 详细执行日志（追踪日志，写txt）
     * @param type $taskid
     * @param type $msg
     */
    public function writeCrondLog($taskid,$msg)
    {
        if($this->_flgTrace){
            error_log("\tCroNd ".  getmypid()."#\t$taskid\t$msg");
        }
    }
    /**
     * 详细执行日志（追踪日志，写txt）
     * @param type $taskid
     * @param type $msg
     */
    public function writeCrondError($taskid,$msg)
    {
            error_log("\tCroNd ".  getmypid()."#\t$taskid\t$msg");
    }    
    /**
     * 更新任务执行状态
     * @param int $ymd yyyymmdd
     * @param int $hour
     * @param string $taskid  哪个任务
     * @param string $lastStatus  本轮最后执行结果
     * @param int $isOkFinal 是否正常结束（预定的跳过也算正常）
     * @param int $isManual  是自动还是手动
     * @throws \ErrorException
     */
    public function updCrondStatus($ymd,$hour,$taskid,$lastStatus,$isOkFinal,$isManual=0)
    {
        $tmp = self::getCopy(array('ymdh'=>$ymd*100+$hour,'taskid'=>$taskid));
        $tmp->load();
        if(strlen($lastStatus)>2000){
                error_log('updCrondStatus_msgTooLong:'.$lastStatus);
                $lastStatus = substr($lastStatus,0,1990)."...";
        }
        $tmp->setField('lastStatus', $lastStatus);
        $tmp->setField('lastRet', $isOkFinal);
        $tmp->setField('isManual', $isManual);
        $r = explode('-',date('i-s'));
        $tmp->setField('theminute', $r[0]);
        $tmp->setField('thesecond', $r[1]);
        try{
            $tmp->saveToDB();
        } catch (\ErrorException $ex) {
            $this->writeCrondLog($taskid, 'update crond task '.$taskid."($ymd$h : $lastStatus) failed:".$ex->getMessage()
                    ."\n".$ex->getTraceAsString());
        }
    }
    /**
     * 建库表
     */
    public function ensureCrondTable()
    {
        $sql = "CREATE TABLE `tb_crondlog_0` (
  `ymdh` bigint(20) NOT NULL,
  `taskid` varchar(64) NOT NULL,
  `lastStatus` varchar(2000) NOT NULL,
  `lastRet` tinyint(4) NOT NULL COMMENT '0: 未正常结束   1：正常结束',
  `isManual` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0:自动   1:手动',
  `theminute` tinyint(4) NOT NULL,
  `thesecond` tinyint(4) NOT NULL,
  `rowVersion` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ymdh`,`taskid`)
) ";
    }
    /**
     * 删除过期日志
     * @param int $dayExpired （默认删除190天前）
     */
    public function removeCrondLogExpired($dayExpired=190)
    {
        $tmp = self::getCopy(null);
        $dt = time()-86400*$dayExpired;
        $db = $tmp->dbWithTablename();
        $db->delRecords($db->kvobjTable(), array('<ymdh'=>date('YmdH',$dt)));
    }
}
