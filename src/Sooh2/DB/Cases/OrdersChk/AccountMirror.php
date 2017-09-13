<?php
namespace Sooh2\DB\Cases\OrdersChk;

/**
 * 本次验证中涉及的账户数据
 *
 * @author wangning
 */
class AccountMirror  extends \Sooh2\DB\KVObj{

    const err_none = 0;
    const err_init = -1;
    
    
    /**
     * 找出有错误的用户
     * @return type
     */
    public function userWithError()
    {
        $db = $this->dbWithTablename();
        $ret = $db->getCol($db->kvobjTable(),'uid',array('!hasError'=>self::err_none));
        return $ret;
    }
    
    /**
     * 获取指定批次用户数
     * （因prepare阶段会清空，所以就是全表）
     * @param type $batchYmd
     */
    public function userCount($batchYmd)
    {
        $db = $this->dbWithTablename();
        return $db->getRecordCount($db->kvobjTable());
    }
    
    public static function install()
    {
        $db = static::getCopy(null)->dbWithTablename();
        $db->exec(array('create table if not exists '.$db->kvobjTable().'('
            . 'uid varchar(64) not null,'
            . 'hasError int not null default '.self::err_init.','
            . 'errors varchar(2000) not null default \'\','
            . 'dtOrderLast int not null default 0,'
            . 'dtUpdate int not null default 0,'
            . 'balance varchar(500) default \'\','
            . 'rowVersion int not null default 0,'
            . 'primary key (uid)'
            . ')'));
    }
}
