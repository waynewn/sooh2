<?php
/**
 * Author: lingtima@gmail.com
 * Time: 2017-06-28 10:03
 */

namespace Sooh2\Valid;

class Regex extends Base
{
    public function __construct($isRequired = false, $min = '', $max = 999999999) {
        parent::__construct($isRequired, $min, $max);
        $this->_type = 'regex';
    }

    public function check($title, $val)
    {
        if(empty($val)){
            if ($this->_isRequired) {
                return ErrMsgFmt::getInstance()->isRequired($title);
            } else {
                return false;
            }
        }

        if (preg_match($this->_min, $val) === 0) {
            return ErrMsgFmt::getInstance()->invalid($title);
        }
    }
}