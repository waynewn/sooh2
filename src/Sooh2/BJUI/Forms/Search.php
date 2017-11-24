<?php
namespace Sooh2\BJUI\Forms;

/**
 * Description of Form
 *
 * @author simon.wang
 */
class Search extends \Sooh2\HTML\Form\Edit{
    public function render($col=6){

        //$s = '<form action="'.$this->_action.'" id="'.$this->_htmlId.'" data-toggle="ajaxform">';
        $s = '<form data-toggle="ajaxform" action="'.$this->_action.'" id="'.$this->_htmlId.'">';
        $s .= '<div style="margin:0; padding:1px 5px 5px;">';

//            <span>门诊号：</span>
//            <input name="obj.code" class="form-control" size="15" type="text">
        
        foreach ($this->hiddens as $k=>$v){
            $s.='<input type=hidden name="'.$k.'" value="'.$v.'">';
        }
        
        //hiddens first

        foreach($this->items as $input)
        {
            $tmp = $input->render('<span>{capt}</span>{input}');
            $s.=''. str_replace(array('<input '), array('<input class="form-control" style="width:200px;" '), $tmp).'';
        }
        
        $uri = \Sooh2\Misc\Uri::getInstance(); 
        $navid = $uri->currentModule()."-".$uri->currentController();
        $s .= '<div class="btn-group">
                <button type="button" class="btn-green" data-icon="search" onclick="BJUI.navtab({fresh:true,id: $(\'body\').data(\'bjui.navtab\').current ,url:\''.$this->_action.'?\'+$(\'#'.$this->_htmlId.'\').serialize()})">开始搜索！</button>
                <button type="reset" class="btn-orange" data-icon="times">重置</button>
            </div></div></form>';
        return $s;
    }
}
