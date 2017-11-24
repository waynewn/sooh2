<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class Password extends \Sooh2\HTML\Item\Base{
    public function render($tpl=null){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        $capt =  $this->_capt?$this->_capt.": ":'';
        $type = 'password';
        if(empty($capt))$type = 'hidden';
        $inputDefine = '<input '.$this->_otStr.' name="'.$this->_name.'" type="' .$type. '" value="'.$this->_val.'" data-rule="'.$isRequire.'">';
        if(!empty($tpl)){
            $s = str_replace(array('{capt}','{input}','{require}'),  array($capt, $inputDefine,$isRequire), $tpl);
        }else{
            $s = "\n".'<label class="row-label">'.$capt.'</label><div class="row-input'.$isRequire.'">'.$inputDefine.'</div>';
        }
        return $s;
    }
}
