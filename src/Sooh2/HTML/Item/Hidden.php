<?php
namespace Sooh2\HTML\Item;

/**
 * Description of Hidden
 *
 * @author simon.wang
 */
class Hidden extends \Sooh2\HTML\Item\Base{
    public function render(){return '<input type=hidden name="'.$this->_name.'" value="'.$this->_val.'">';}
}
