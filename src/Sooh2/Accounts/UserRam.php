<?php
namespace Sooh2\Accounts;

class UserRam extends UserDsk
{

    /**
     * 获取user
     * @param int $userId
     * @return \Sooh2\Accounts\UserRam
     */
    public static function getCopy($userId)
    {
        //userdsk做过userid 到 array(userid=>xxx)的转换
        return parent::getCopy($userId);
    }
//     protected function onInit()
//     {
//         $this->className = 'UserDsk';
//         parent::onInit();
//         $this->_tbName = 'tb_user_{i}';//表名的默认模板
//     }
}

