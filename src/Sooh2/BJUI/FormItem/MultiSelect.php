<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class MultiSelect extends \Sooh2\HTML\Item\Base{
    public function render(){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        if(!is_array($this->_val)){
            $this->_val = explode(',', $this->_val);
        }
        $s = "\n".'<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>'
                .'<div class="row-input'.$isRequire.'"><select data-toggle="selectpicker" multiple="" name="'.$this->_name.'[]">';
        if(is_array($this->_optionsData)){
            foreach($this->_optionsData as $k=>$v){
                $s .= "<option value=\"$k\" ".(in_array($k,$this->_val)?"selected=\"\"":'').">$v</option>";
            }
        }else{
            throw new \ErrorException('not support yet');
        }
        
        $s.='</select></div>';
        return $s;
    }
}
