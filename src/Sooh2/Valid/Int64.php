<?php
namespace Sooh2\Valid;

/**
 * 字符串的验证
 * （增加类型的时候，需要同时增加Valid\ErrMsgFmt里对应类型的处理，Valid\Base里的支持代码）
 *
 * @author simon.wang
 */
class Int64 extends Base{
    public function __construct($isRequired = false, $min = 0, $max = 999999999) {
        parent::__construct($isRequired, $min, $max);
        $this->_type = self::type_int;

    }
}
