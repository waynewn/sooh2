<?php
namespace Sooh2\DB\Interfaces;

interface DB
{
    /**
     * kvobj 专用的一个函数，用于临时返回当前对应的表
     * @param type $tb
     */
    public function kvobjTable($tb=null);
    public function useDB($dbname);
    public function getOne($obj, $field, $where=null, $sortgrpby=null);
    public function getRecord($obj, $fields, $where=null, $sortgrpby=null);
    public function getRecords($obj, $fields, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0);
    public function getCol($obj, $field, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0);
    public function getPair($obj, $fieldKey, $fieldVal, $where=null, $sortgrpby=null,$pageSize=null,$rsFrom=0);
    public function getRecordCount($obj, $where=null);
    
    public function updRecords($obj,$fields,$where=null);
    public function addRecord($obj,$fields,$pkey=null);
    public function delRecords($obj,$where=null);
    
    public function safestr($str,$field=null,$obj=null);
    /**
     * do not write error-log of special error
     * @param int $skipThisError
     * @return \Sooh2\DB\Interfaces\DB
     */
    public function skipErrorLog($skipThisError);
    public function lastCmd();
    public function exec($cmds);
    public function fetchResultAndFree($rsHandle);//部分数据库，比如mysql 用的上
    
    // test.tb1, ['pkey'=>vale1],['field1'=>'value2'],['field1','+field1']
    public function ensureRecord($obj,$pkey,$fields,$arrMethodFields);
//     {
//         $exists = $this->getRecord($obj,'*',array('AND'=>$pkey));
//         if(!empty($exists)){
//             $method = array_shift($arrMethodFields);
//             $tmp = array();
//             if($method==='='){
//                 foreach($arrMethodFields as $k){
//                     $tmp[$k]=$fields[$k];
//                 }
//             }else{
//                 foreach($arrMethodFields as $k){
//                     $tmp[$k]="$k+".$fields[$k];????safe_string
//                 }
//             }
//             $this->updRecord($obj,$tmp,$pkey);
//         }else{
//             foreach($pkey as $k=>$v){
//                 $fields[$k]=$v;
//             }
//             $this->addRecord($obj,$fields);
//         }
//     }
}

