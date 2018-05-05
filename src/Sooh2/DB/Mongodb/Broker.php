<?php
namespace Sooh2\DB\Mongodb;

/**
 * 
 * @author simon.wang
 */

class Broker extends Cmd implements \Sooh2\DB\Interfaces\DBReal
{
    protected $_tmpKvobjTable;
    public function getConn() {
        return $this->connection;
    }
    public function kvobjTable($tb=null)
    {
        if($tb==null){
            return $this->_tmpKvobjTable;
        }else{
            return $this->_tmpKvobjTable=$tb;
        }
    }
    public function addRecord($obj,$fields,$pkey=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        list($dbname,$tbname)=$this->fmtObj($obj);
        if(is_array($pkey)){
            $document = array_merge($fields,$this->buildIdFilter($pkey));
            foreach($pkey as $k=>$v){
                $document[$k]=$v;
            }
        }else{
            $document = $fields;
        }
        $this->_lastCmd = "$dbname:db.$tbname.insert(". json_encode($document).')';
        $bulk = new \MongoDB\Driver\BulkWrite;
        try{
            $bulk->insert($document);
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 10000);//插入动作，这里加个超时吧
            \Sooh2\Misc\Loger::getInstance()->lib_trace("TRACE: try @". (empty($this->connection->server)?"":$this->connection->server) ." ".$this->_lastCmd);
            $result = $this->connection->connected->executeBulkWrite("$dbname.$tbname", $bulk, $writeConcern);
            $affectedRows = $result->getInsertedCount(); 
            
            return $affectedRows>0?$affectedRows:true;
        }catch(\MongoDB\Driver\Exception\BulkWriteException $ex){
            if('E11000 duplicate key'==substr($ex->getMessage(),0,20)){
                $ex2 = new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::duplicateKey, $ex->getMessage(), $this->_lastCmd);
                $ex2->keyDuplicated = '_id';
                throw $ex2;
            }else{
                throw $ex;
            }
        }catch(\ErrorException $ex){
            \Sooh2\Misc\Loger::getInstance()->sys_warning("Error on mongo-insert:".$this->_lastCmd .":".$ex->getMessage());
            return false;
        }

    }
    protected function _updRecordOne($obj,$fields,$_id,$whereLeft=null)
    {
        $where = array('_id'=>$_id);
        $rowVersion = \Sooh2\DB::version_field();
        
        list($dbname,$tbname)=$this->fmtObj($obj);
        
        $objLock = $dbname.'.Sooh2lockkey_'.$tbname;
        
        try{//获取更新锁
            $this->addRecord($objLock, array('createTime'=>date('Y-m-d H:i:s')),$where);
        } catch (\Sooh2\DB\DBErr $ex) {
            if($ex->keyDuplicated){
                return 0;
            }else{
                throw $ex;
            }
        }
        if(!empty($whereLeft[$rowVersion])){//检查rowVersion字段
            $r = $this->getRecord($obj, '*', $where);
            if(!is_array($r) || $r[$rowVersion]!=$whereLeft[$rowVersion]){
                $this->delRecords($objLock,$where);
                return 0;
            }
        }
        //执行更新动作
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update(
            array('_id' => $_id),
            array('$set'=>$fields),
            array('multi' => false, 'upsert' => false)
        );

        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        $this->_lastCmd = "$dbname:db.$tbname.update({_id:\"". $_id.'",'. json_encode($fields).',{upsert:false,multi:false})';
        \Sooh2\Misc\Loger::getInstance()->lib_trace("TRACE: try @". (empty($this->connection->server)?"":$this->connection->server) ." ".$this->_lastCmd);
        $ret = $this->connection->connected->executeBulkWrite("$dbname.$tbname", $bulk, $writeConcern);
       
        $this->delRecords($objLock,$where);
        return $ret->getModifiedCount();
    }
    public function updRecords($obj,$fields,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }

        $filter = $this->buildIdFilter($where);
        $whereLeft = $this->buildRowVersionWhere($where);
        if(is_array($filter['_id']['$in'])){
            $changed = 0;
            foreach ($filter['_id']['$in'] as $_id){
                $ret = $this->_updRecordOne($obj, $fields, $_id,$whereLeft);
                if($ret===1){
                    $changed++;
                }
            }
            
        }else{
            $changed = $this->_updRecordOne($obj, $fields, $filter['_id'],$whereLeft);
        }
        if($changed===0){
            return true;
        }else{
            return $changed;
        }
    }

    public function delRecords($obj,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        list($dbname,$tbname)=$this->fmtObj($obj);
        try{
            $bulk = new \MongoDB\Driver\BulkWrite;
            if(!empty($where)){
                $filter=$this->buildIdFilter($where);
            }else{
                $filter=array();
            }
            $bulk->delete($filter, array('limit' => 0));// limit 为 1 时，删除第一条匹配数据
            $this->_lastCmd = "$dbname:db.$tbname.remove(". json_encode($filter).')';
            \Sooh2\Misc\Loger::getInstance()->lib_trace("TRACE: try @". (empty($this->connection->server)?"":$this->connection->server) ." ".$this->_lastCmd);
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 0);//默认不设置ms-timeout
            $result = $this->connection->connected->executeBulkWrite("$dbname.$tbname", $bulk, $writeConcern);
            $affectedRows = $result->getDeletedCount(); 
            return $affectedRows>0?$affectedRows:true;
        }catch(\ErrorException $ex){
            \Sooh2\Misc\Loger::getInstance()->sys_warning("Error on mongo-insert:".$this->_lastCmd .":".$ex->getMessage());
            return false;
        }
        
    }
    public function getRecordCount($obj, $where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        list($dbname,$tbname)=$this->fmtObj($obj);
        $filter = $this->buildIdFilter($where);
        $this->_lastCmd = "$dbname:db.$tbname.find(". json_encode($filter).').count()';

        $options = [
            'projection' => array('_id' => 1),
        ];

        //". json_encode($options);
        // 查询数据
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->connection->connected->executeQuery("$dbname.$tbname", $query);
        return $cursor->count();
    }
    public function getRecords($obj, $fields, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        if(!$this->connection->connected){
            $this->connect();
        }
        list($dbname,$tbname)=$this->fmtObj($obj);
        $filter = $this->buildIdFilter($where);

        $this->_lastCmd = "$dbname:db.$tbname.find(". json_encode($filter);
        $options = [
            'projection' => array('_id' => 0),
        ];
        if($fields!=='*'){
            if(is_string($fields)){
                $fields = explode(',', $fields);
            }

            foreach($fields as $k){
                $options['projection'][$k]=1;
            }
            $this->_lastCmd.=",".json_encode($options['projection']);
        }
        $this->_lastCmd .=")";

        if(!empty($sortgrpby)){
            $arr = explode(' ',trim($sortgrpby));
            $mx = sizeof($arr);
            $orderby=array();
            for($i=0;$i<$mx;$i+=2){
                $k = $arr[$i];
                $v = $arr[$i+1];
                switch($k){
                    case 'rsort':
                        $orderby[$v]= -1;
                        break;
                    case 'sort':
                        $orderby[$v]= 1;
                        break;
                    default:
                        throw new \ErrorException('invalid sortgroup given:'.$sortgrpby);
                }
            }
        }
        
        if(!empty($orderby)){
            $options['sort']=$orderby;
            $this->_lastCmd.=".sort(".json_encode($orderby).")";
        }
        if($pageSize!==null){
            $options['limit']=$pageSize;
            $this->_lastCmd.=".limit(".$pageSize.")";
        }
        if($rsFrom>0){
            $options['skip']=$rsFrom;
            $this->_lastCmd.=".skip(".$rsFrom.")";
        }
        \Sooh2\Misc\Loger::getInstance()->lib_trace("TRACE: try @". (empty($this->connection->server)?"":$this->connection->server) ." ".$this->_lastCmd);
        //". json_encode($options);
        // 查询数据
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->connection->connected->executeQuery("$dbname.$tbname", $query);
        $ret = array();
        foreach ($cursor as $document) {
            $ret[]=json_decode( json_encode( $document),true);
        }

        return $ret;
    }

    public function getRecord($obj, $fields, $where=null, $sortgrpby=null)
    {
        $rs = $this->getRecords($obj, $fields, $where, $sortgrpby, 1, 0);
        if(!empty($rs)){
            return $rs[0];
        }else{
            return array();
        }
    }

    public function fetchResultAndFree($rsHandle)
    {
        return $rsHandle;
    }
    
    public function getOne($obj, $field, $where=null, $sortgrpby=null){
        $rs = $this->getRecords($obj, $field, $where, $sortgrpby, 1, 0);
        if(!empty($rs)){
            return $rs[0][$field];
        }else{
            return array();
        }
    }

    public function getCol($obj, $field, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        $ret = array();
        $rs = $this->getRecords($obj, $field, $where, $sortgrpby,$pageSize,$rsFrom);
        if(!empty($rs)){
            foreach($rs as $r){
                $ret[]=$r[$field];
            }
            return $ret;
        }else{
            return array();
        }
    }
    public function getPair($obj, $fieldKey,$fieldVal, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        $ret = array();
        $rs = $this->getRecords($obj, array($fieldKey,$fieldVal), $where, $sortgrpby,$pageSize,$rsFrom);
        if(!empty($rs)){
            foreach($rs as $r){
                $ret[$r[$fieldKey]]=$r[$fieldVal];
            }
            return $ret;
        }else{
            return array();
        }
    }

    public function ensureRecord($obj,$pkey,$fields,$fieldsWithValOnUpdate)
    {
        throw new \ErrorException('todo(mongodb-ensure-record)');
    }
    public function skipErrorLog($skipThisError) {
        throw new \ErrorException('todo(mongodb-skipErrorLog)');
    }
    public function safestr($str, $field = null, $obj = null) {
        return $str;
    }
}

