<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class DateTime extends \Sooh2\HTML\Item\Base{
    public function render1(){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
        }else{
            $isRequire = '';
        }
        $s = "\n".'<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>
    <div class="row-input'.$isRequire.'"><input name="'.$this->_name.'" type="text" value="'.$this->_val.'" data-rule="'.$isRequire.'"></div>';
        return $s;
    }

    public function render($tpl=null){
        $_capt = $this->_capt?$this->_capt.": ":'';

        if(strpos($this->_otStr , 'time') !== false){
            $options = " data-pattern = 'yyyy-MM-dd HH:mm' ";
        }
        \Prj\Loger::out($options);

        $html = <<<html
            <label class="row-label">$_capt</label>
            
                <input name="{$this->_name}" type="text" value="{$this->_val}" $options  data-toggle="datepicker" placeholder="点击选择日期">
           
html;
        return $html;
    }
}
