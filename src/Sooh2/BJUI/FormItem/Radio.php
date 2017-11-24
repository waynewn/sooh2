<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 标准文本输入框
 *
 * @author simon.wang
 */
class Radio extends \Sooh2\HTML\Item\Base{
    public function render(){
        if($this->_checker && $this->_checker->_isRequired){
            $isRequire = ' required';
            $isRule='checked';
        }else{
            $isRequire = '';
        }
        $s = "\n".'<label class="row-label">'.($this->_capt?$this->_capt.": ":'').'</label>'
                .'<div class="row-input'.$isRequire.'">';
        if(is_array($this->_optionsData)){
            foreach($this->_optionsData as $k=>$v){
                $s .= "<input type=\"radio\"  data-rule=\"".$isRule."\" data-toggle=\"icheck\" name=\"".$this->_name."\" value=\"$k\" data-label=\"".$v."\" ".($k==$this->_val?"checked=\"\"":'')."/>";
            }
        }else{
            throw new \ErrorException('not support yet');
        }
        
        $s.='</div>';
        return $s;
    }
}

/*
 * id=\"".$this->_name."\"
  <label class="row-label">性别</label>
        <div class="row-input required">
            <input type="radio" name="custom.isshow" id="j_custom_sex1" data-toggle="icheck" value="true" data-rule="checked" data-label="男  ">
            <input type="radio" name="custom.isshow" id="j_custom_sex2" data-toggle="icheck" value="false" data-label="女">
        </div>

<input type="radio" data-toggle="icheck" name="isLink" value="0" data-label="链接" checked="">
<input type="radio" name="custom.isshow" id="j_custom_sex1" data-toggle="icheck" value="true" data-label="男&nbsp;&nbsp;" style="position: absolute; top: -20%; left: -20%; display: block; width: 140%; height: 140%; margin: 0px; padding: 0px; background: rgb(255, 255, 255); border: 0px; opacity: 0;" class="ok">
<input type="radio" data-toggle="icheck" name="custom.isshow" value="true" data-label="男&nbsp;&nbsp;" >
 * */

