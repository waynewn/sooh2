<?php
interface IMQ
{
    /**
     * push data to mq
     * @param string $mqid  db.tb.typeid
     * @param string $strData
     * @return boolean if success
     */
    public function mq_push($mqid,$strData);
    
    /**
     * get id of current process
     * @param string $mqid  db.tb.typeid
     * @return int64
     */
    public function mq_identifyme($mqid);
    /**
     * find a task, lock it with  worker-processid
     * @param string $mqid  db.tb.typeid
     * @param int64 $mymqid
     * @return null or strData
     */
    public function mq_mark($mqid,$mymqid);
    /**
     * pop taskdata out of mq when job done successfully
     * @param string $mqid  db.tb.typeid
     * @param string $strData
     * @return bool if success
     */
    public function mq_pop($mqid,$strData);
    
    /**
     * recover locked taskdata, prepare for next call
     * @param string $mqid  db.tb.typeid
     * @param string $strData
     * @return bool if success
     */
    public function mq_recover($mqid,$strData);
}