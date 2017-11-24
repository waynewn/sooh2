<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class Show extends \Sooh2\HTML\Item\Base{
    public function render(){

        $s = "\n".'<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>
    <div class="row-input">'.(is_array($this->_optionsData)?$this->_optionsData[$this->_val]:$this->_val).'</div>';
        return $s;
        
    }
    public function isConsts(){return true;}
}
