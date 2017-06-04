<?php
namespace Sooh2\Accounts;
class User extends \Sooh2\DB\KVObj\KVObjRW
{
    public static function getCopy($userid)
    {
        if(empty($userid)){
            return UserDsk::getCopy(null);
        }else{
            return parent::getCopy(array('userId'=>$userid));
        }
    }

    protected function onInit()
    {
        $this->needTransData = true;
        $this->_reader = UserRam::getCopy(current($this->_pkey));
        $this->_writer = UserDsk::getCopy(current($this->_pkey));
    
    }    
}