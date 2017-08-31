<?php
namespace Sooh2\Messager;

/**
 * 记录消息发送结果的记录的类
 * 
 * @author simon.wang
 */
class MsgSentLog extends \Sooh2\DB\KVObj implements Interfaces\MsgSendLog{
    public static function getCopy($logid)
    {
        return parent::getCopy(array('logid'=>$logid));
    }
    public static function createNew($title,$content,$user,$ways,$evtmsgid)
    {
        $dt = explode('.', microtime(true));
        
        //$dt[0] = date('YmdHis',$dt[0]);
        $dt[1]=substr($dt[1].'0000',0,4);
        $retry=2;
        while($retry>0){
            $retry--;
            $id = $dt[0].$dt[1].rand(1000, 9999);
            $tmp = static::getCopy($id);
            $tmp->setField('evtid',$evtmsgid);
            $tmp->setField('ymdhis',date('YmdHis'));
            $tmp->setField('msgtitle',$title);
            $tmp->setField('msgcontent',$content);
            $tmp->setField('users',$user);
            $tmp->setField('ways',$ways);
            if(!is_array($ways)){
                $ways = array($ways);
            }
            $retarr = array();
            foreach ($ways as $k){
                $retarr[$k]='failed(wait to send)';
            }
            $tmp->setField('sentret',$retarr);

            try{
                $ret = $tmp->saveToDB();
                if($ret){
                    return $tmp;
                }
            }catch(\Sooh2\DB\DBErr $e){
                if($e->keyDuplicated){
                    static::freeCopy($tmp);
                }else{
                    throw $e;
                }
            }
        }
        return null;
        
    }
    
    public function freeme(){
        static::freeCopy($this);
    }
    public function getUsers(){return $this->getField('users');}
    public function getContent(){return $this->getField('msgcontent');}
    public function getTitle(){return $this->getField('msgtitle');}
    public function setResult($wayid,$ret){
        $cur = $this->getField('sentret');
        $cur[$wayid]=$ret;
        $this->setField('sentret', $cur);
    }
}
