<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 日期输入框
 *
 * @author simon.wang
 */
class TextDate extends \Sooh2\HTML\Item\Base{
    public function render($tpl=null){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required;';
        }else{
            $isRequire = '';
        }
        if($this->_val){
            $val=$this->_val;
        }else{
            $val=date('Y-m-d');
        }
        $capt =  $this->_capt?$this->_capt.": ":'';
        $inputDefine = '<input name="'.$this->_name.'" data-toggle="datepicker" type="text" value="'.$val.'" data-rule="'.$isRequire.'date">';
        if(!empty($tpl)){
            $s = str_replace(array('{capt}','{input}','{require}'),  array($capt, $inputDefine,$isRequire), $tpl);
        }else{
            $s = "\n".'<label class="row-label">'.$capt.'</label><div class="row-input'.$isRequire.'">'.$inputDefine.'</div>';
        }
        return $s;
    }
}
