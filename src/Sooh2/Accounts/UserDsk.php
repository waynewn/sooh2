<?php
namespace Sooh2\Accounts;

class UserDsk extends \Sooh2\DB\KVObj
{
    public static function sqlCreate($db,$tbname = 'tb_user_{i}')
    {
        $sqls =array();
        $sqls[] = str_replace('{tbname}', $tbname, "create table if not exits {tbname}("
            ."userId bigint not null default 0,"
            ."userStatus int not null default 0,"
            ."passwd varchar(36) not null default '',"
            .''
            ."nickname varchar(16) not null default '',"
            ."rowLock varchar(200) not null default '',"
            . \Sooh2\DB::version_field()." int not null default 0,"
            ."primary key (userId)"
            .")");

            //todo: alter table

        
        return str_replace('{tbname}',$tbname, $sqls[0]);
    }
    protected function onInit()
    {
        //$this->className = 'UserDsk';
        
        parent::onInit();
        $this->field_locker='rowLock';//  悲观锁用的字段名，默认使用'rowLock'，设置为null表明不需要悲观锁
        $this->_tbName = 'tb_user_{i}';//表名的默认模板
    }
    //     /**
    //      * 针对主键是一个数字串的情况使用取余的计算方式，默认取尾数，这里可以改成使用开头部分
    //      * 设置比较长度，改100000，userid用100亿，取前几位而不是末几位，流水用后面的数字递增
    //      * @param string $n
    //      */
    //     protected static function calcPkeyValOfNumber($n)
    //     {
    //         return substr(sprintf('%010d',$n),0,-4)-0;
    //     }
    /**
     * 获取user
     * @param int $userId
     * @return \Sooh2\Accounts\UserDsk
     */
    public static function getCopy($userId)
    {
        if(empty($userId)){
            return parent::getCopy(null);
        }else{
            return parent::getCopy(array('userId'=>$userId));
        }
    }
    /**
     * 创建一个新的用户，返回user类（如果userId冲突，连续尝试10次）
     * @param array $defaultVals 默认字段值列表
     * @param int $speIndex 是否指定splitIndex
     * @return \Sooh2\Accounts\UserDsk
     */
    public static function createNew($defaultVals=array(),$speIndex=null)
    {
        if($speIndex===null){
            $speIndex = rand(1,99999);
        }
        $retry = 10;
        while($retry>0){
            $retry--;
            if($speIndex<=99999){
                $userId = rand(10000,99999).$speIndex;
            }else{
                $userId = $speIndex;
            }
            $tmp = static::getCopy($userId);
            foreach($defaultVals as $k=>$v){
                $tmp->setField($k,$v);
            }
            try{
                $ret = $tmp->saveToDB();
                if($ret){
                    $tmp->_lock = \Sooh2\DB\LockInfo::factory('');
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
    /**
     * 获取用户userId
     */
    public function userId()
    {
        return current($this->_pkey);
    }
}

