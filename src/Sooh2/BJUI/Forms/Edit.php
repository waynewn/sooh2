<?php
namespace Sooh2\BJUI\Forms;

/**
 * Description of Form
 *
 * @author simon.wang
 */
class Edit extends \Sooh2\HTML\Form\Edit{
    public function render($col=2){

        $inputItemTpl = "\n".'<label class="row-label">{capt}</label><div class="row-input {require}">{input}</div>';
        
        $s = '<form action="'.$this->_action.'" id="'.$this->_htmlId.'" data-toggle="ajaxform">';
        foreach ($this->hiddens as $k=>$v){
            $s.='<input type=hidden name="'.$k.'" value="'.$v.'">';
        }
        
        //hiddens first
        $s .= '<div class="bjui-row col-'.$col.'">';
        foreach($this->items as $input)
        {
            if(substr(get_class($input),-8)=='Textarea'){
                $s.='</div><div class="bjui-row col-1">'.$input->render().'</div><div class="bjui-row col-'.$col.'">';
            }else{
                $s.=''.$input->render($inputItemTpl).'';
            }
        }
        
        $s .= '</div>';
        $s .= "</form>";
        return $s;
    }

}
