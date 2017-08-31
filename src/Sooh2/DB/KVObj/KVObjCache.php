<?php
namespace Sooh2\DB\KVObj;

/**
 * cache模式，比如mysql+redis组合
 *
 * @author simon.wang
 */
class KVObjCache extends KVObjRW{
    /**
     * cache模式下，主库更新了，从库也需要更新(忽略更新从库失败的情况)
     */
    public function saveToDB($func_update = null, $maxRetry = 3) {
        $verField = \Sooh2\DB::version_field();
        if($this->_reader->exists()){
            $readerVersion = $this->_reader->getField($verField);
        }else{
            $readerVersion=-1;
        }
        $ret = parent::saveToDB($func_update, $maxRetry);
        if($ret){
            try{
                if($readerVersion==-1){//如果reader没有，直接insert即可
                    $this->_reader->forceInsert();
                }else{//如果reader之前有,前面一步给改了，需要回退一个版本号
                    $this->_reader->setField($verField, \Sooh2\Util::autoIncCircled($this->_reader->getField($verField),-1));
                }
                $this->_reader->saveToDB();
            }catch(\ErrorException $e){
                \Sooh2\Misc\Loger::getInstance()->app_warning("Error on update cache of ". get_called_class().' with'. json_encode($this->_reader->pkey()).':'.$e->getMessage());
                //出于未知原因可能导致rowVersion不对了导致更新失败，这里先删除一次
                $db = $this->_reader->dbWithTablename();
                $db->delRecords($db->kvobjTable(),$this->_reader->pkey());
                $this->_reader->forceInsert();
                $this->_reader->setField($verField, $this->_writer->getField($verField));
                $this->_reader->saveToDB();
            }
        }
        return $ret;
    }
    public function load($forceReload = false) {
        $ret = parent::load($forceReload);
        if(!is_array($ret)){//reader 找不到记录，需要再通过writer找一下，然后覆盖reader
            return $this->loadFromDisk();
        }else{
            return $ret;
        }
    }
    protected function loadFromDisk() {
        $isReaderExists = $this->_reader->exists();
        $ret = parent::loadFromDisk();
        if(is_array($ret)){
            try{//根据情况落地reader，此时忽略入库触发的错误
                //如果reader没有，需要同步数据（插入记录）
                //如果reader有了，对load这一步来说，不用落地
                if($isReaderExists==false){
                    $this->_reader->forceInsert();
                    $ret = $this->_reader->saveToDB();
                    if($ret==false){
                        \Sooh2\Misc\Loger::getInstance()->sys_warning('data load from disk ok,but write cache failed ('.json_encode($this->_pkey).'),assume same as this one ');
                    }
                }
            }catch(\ErrorException $e){
                \Sooh2\Misc\Loger::getInstance()->sys_warning('data load from disk ok,but write cache failed ('.json_encode($this->_pkey).'):'.$e->getMessage());
            }
        }
        return $ret;
    }
}
