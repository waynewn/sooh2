<?php
namespace Sooh2\Valid;
/**
 * Description of Base
 *
 * @author simon.wang
 */
class Base {
    /**
     * 
     * @param bool $isRequired
     * @param string $type
     * @param int $min 最小（值|长度|...)
     * @param int $max 最大（值|长度|...)
     */
    public function __construct($isRequired=false,$min=0,$max=999999999) {
        $this->_isRequired=$isRequired;
        $this->_min = $min;
        $this->_max = $max;
    }
    public $_isRequired;
    public $_type;
    public $_min;
    public $_max;
    
    const type_string='str';
    const type_int = 'int';
    const type_float= 'float';
    const type_ymd = 'ymd';
    const type_phone='phone';
    const type_email='email';
    const type_ip='ip';
    public function check($title,$val)
    {
        if(empty($val)){
            if ($this->_isRequired) {
                return ErrMsgFmt::getInstance()->isRequired($title);
            } else {
                return false;
            }
        }
        switch ($this->_type){
            case 'str':
                $len = mb_strwidth($val,'utf-8');
                if($len<$this->_min){
                    return ErrMsgFmt::getInstance()->strTooShort($title, $this->_min, $this->_max);
                }elseif($len>$this->_max){
                    return ErrMsgFmt::getInstance()->strTooLong($title, $this->_min, $this->_max);
                }
                break;
            case 'int':
                $other = str_replace(array(0,1,2,3,4,5,6,7,8,9), '', $val);
                if(strlen($other)){
                    return ErrMsgFmt::getInstance()->invalid($title);
                }elseif($val<$this->_min){
                    return ErrMsgFmt::getInstance()->strTooShort($title, $this->_min, $this->_max);
                }elseif($val>$this->_max){
                    return ErrMsgFmt::getInstance()->strTooLong($title, $this->_min, $this->_max);
                }
                break;
            default:
                throw new \ErrorException('tinput check todo');
        }
        return false;
    }
}
