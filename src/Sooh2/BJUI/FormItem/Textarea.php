<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 多行文本输入框
 * 设置了指定宽度 600,
 *
 * @author simon.wang
 */
class Textarea extends \Sooh2\HTML\Item\Base{
    public function render(){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        $s = '<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>
    <div class="row-input'.$isRequire.'"><textarea name="'.$this->_name.'" style="width:600px">'.$this->_val.'</textarea></div>';
        return $s;
        
    }
}
