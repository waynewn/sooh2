<?php

namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class Checkbox extends \Sooh2\HTML\Item\Base{
    public function render($tpl = null){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        $s = "\n".'<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label><div class="row-input">';
        if(is_array($this->_optionsData)){
            foreach($this->_optionsData as $k=>$v){
                //这里的_val应该是个数组
                $s .= $this->_one($this->_name, $k,$v, $this->_val);
            }
        }else{

            throw new \ErrorException('not support yet');
        }
        
        $s.='</div>';
        return $s;
    }
    protected function _one($inputname,$optionsname,$optionvalue,$varArr)
    {
        $checked = false;
        foreach ((array)$varArr as $v){
            if($v == $optionsname){
                $checked = true;
            }
        }
        $this->_otStr = str_replace('readonly' , 'disabled' , $this->_otStr );
        return '<input '.$this->_otStr .' type="checkbox" name="'.$inputname.'[]" id="j_form_checkbox_'.$optionsname.'" value="'.$optionsname.'" data-toggle="icheck" data-label="'.$optionvalue.'" '.($checked?'checked="checked"':'').'><br/>';
    }
}
