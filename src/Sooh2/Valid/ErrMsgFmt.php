<?php
namespace Sooh2\Valid;
/**
 * 格式化错误信息的类
 * 
 * @author simon.wang
 */
class ErrMsgFmt {
    protected static $_instance = null;
    /**
     * 
     * @param \Sooh2\Valid\ErrMsgFmt $newInstance
     * @return \Sooh2\Valid\ErrMsgFmt
     */
    public static function getInstance($newInstance=null)
    {
        if($newInstance){
            self::$_instance = $newInstance;
        }elseif(self::$_instance==null){
            self::$_instance = new ErrMsgFmt;
        }
        return self::$_instance;
    }
    public function isRequired($title)
    {
        return $title.'不能为空';
    }
    public function strTooLong($title,$min,$max)
    {
        return $title.'的长度应该在'.$min.'到'.$max.'之间';
    }
    public function strTooShort($title,$min,$max)
    {
        return $title.'的长度应该在'.$min.'到'.$max.'之间';
    }
    public function numTooSmall($title,$min,$max)
    {
        return $title.'应该在'.$min.'到'.$max.'之间';
    }
    public function numTooBig($title,$min,$max)
    {
        return $title.'应该在'.$min.'到'.$max.'之间';
    }
    public function invalid($title)
    {
        return $title.'的格式非法';
    }
}
