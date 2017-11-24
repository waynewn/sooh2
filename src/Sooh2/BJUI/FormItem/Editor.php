<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class Editor extends \Sooh2\HTML\Item\Base{
    public function render(){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        $s = '<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>
    <div class="row-input'.$isRequire.'"><textarea name="'.$this->_name.'" style="width: 700px;" data-toggle="kindeditor" data-minheight="200">'.$this->_val.'</textarea></div>';
        return $s;
        
    }
}
