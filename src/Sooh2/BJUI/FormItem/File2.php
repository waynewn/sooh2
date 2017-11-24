<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框（普通文件）
 *
 * @author simon.wang
 */
class File2 extends \Sooh2\HTML\Item\Base{
    public function render($tpl=null){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
            $isRequire2='true';
        }else{
            $isRequire = '';
            $isRequire2='\'\'';
        }
        $capt =  $this->_capt?$this->_capt.": ":'';
        $otStr= $this->_otStr?$this->_otStr:'';
        $defaultVal = $this->_val?$this->_val:'';
        $type = 'file';
        $inputDefine = '<input type="' .$type. '" data-name="'.$this->_name.'" data-toggle="webuploader" data-options="
                {
                    pick: {label: \'点击选择文件\'},
                    server: \''.$otStr.'\',
                    fileNumLimit: 1,
                    formData: {dir:\'\'},
                    required: '.$isRequire2.',
                    uploaded: \''.$defaultVal.'\',
                    basePath: \'\',
                    accept: {
                        title: \'图片\',
                        extensions: \'html,htm\',
                        mimeTypes: \'.html,.htm\'
                    }
                }">';
        if(!empty($tpl)){
            $s = str_replace(array('{capt}','{input}','{require}'),  array($capt, $inputDefine,$isRequire), $tpl);
        }else{
            $s = "\n".'<label class="row-label">'.$capt.'</label><div class="row-input'.$isRequire.'">'.$inputDefine.'</div>';
        }
        return $s;
    }
}
