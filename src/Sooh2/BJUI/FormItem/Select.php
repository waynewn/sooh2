<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class Select extends \Sooh2\HTML\Item\Base{
    public function render($tpl=null){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        if(strpos($this->_otStr , 'readonly')!== false){
            $disable = 'disabled="disabled"';
        }else{
            $disable = '';
        }
        $s = "\n".'<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>'
                .'<div class="row-input'.$isRequire.'" style="display:inline-block"><select '.$disable.' data-toggle="selectpicker" name="'.$this->_name.'">';
        if(is_array($this->_optionsData)){
            foreach($this->_optionsData as $k=>$v){
                $s .= "<option value=\"$k\" ".($k==$this->_val?"selected=\"\"":'').">$v</option>";
            }
        }else{
            throw new \ErrorException('not support yet');
        }
        
        $s.='</select></div>';
        return $s;
    }
}
