<?php
namespace Sooh2\DB\Mongodb;



class Broker extends Cmd implements \Sooh2\DB\Interfaces\DBReal
{
    protected $_tmpKvobjTable;
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
            $document = $fields;
            if(sizeof($pkey)==1){
                $document['_id'] = current($pkey);
            }else{
                $document['_id'] = new \MongoDB\BSON\ObjectID;
            }
            
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
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 10000);//插入动作，这里加个超时吧
            $result = $this->connection->connected->executeBulkWrite("$dbname.$tbname", $bulk, $writeConcern);
            $affectedRows = $result->getInsertedCount(); 
            return $affectedRows>0?$affectedRows:true;
        }catch(\ErrorException $ex){
            \Sooh2\Misc\Loger::getInstance()->sys_warning("Error on mongo-insert:".$this->_lastCmd .":".$ex->getMessage());
            return false;
        }
        
    }
    public function updRecords($obj,$fields,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        $this->_lastCmd = 'update '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '. $this->buildFieldsForUpdate($fields)
            .$this->buildWhere($where);
        
        $rs0 = $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        
        $affectedRows = mysqli_affected_rows($this->connection->connected);
        return $affectedRows>0?$affectedRows:true;
    }

    public function delRecords($obj,$where=null){
        if(!$this->connection->connected){
            $this->connect();
        }
        list($dbname,$tbname)=$this->fmtObj($obj);
        $filter = $this->buildWhere($where);
        try{
            $bulk = new \MongoDB\Driver\BulkWrite;
            if(!empty($filter)){
                $bulk->delete(array('x' => 2), array('limit' => 0));// limit 为 1 时，删除第一条匹配数据
            }else{
                $bulk->delete(array(), array('limit' => 0));
            }

            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 0);//默认不设置ms-timeout
            $result = $this->connection->connected->executeBulkWrite("$dbname.$tbname", $bulk, $writeConcern);
            $affectedRows = $result->getDeletedCount(); 
            return $affectedRows>0?$affectedRows:true;
        }catch(\ErrorException $ex){
            \Sooh2\Misc\Loger::getInstance()->sys_warning("Error on mongo-insert:".$this->_lastCmd .":".$ex->getMessage());
            return false;
        }
        
    }
    public function getRecordCount($obj, $where=null){
        throw new \ErrorException('todo(mongodb-recordcount)？？？？？？？？？？？？？？？？？？？？？？？？');
    }
    public function getRecords($obj, $fields, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0){
        if(!$this->connection->connected){
            $this->connect();
        }
        list($dbname,$tbname)=$this->fmtObj($obj);
        $filter = $this->buildWhere($where);
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

        //". json_encode($options);
        // 查询数据
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->connection->connected->executeQuery("$dbname.$tbname", $query);
        $ret = array();
        foreach ($cursor as $document) {
            $ret[]=json_decode( json_encode( $document),true);
        }

        return $r;
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
        $rs = $this->getRecords($obj, $field, $where, $sortgrpby, 1, 0);
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
        $rs = $this->getRecords($obj, array($fieldKey,$fieldVal), $where, $sortgrpby, 1, 0);
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

