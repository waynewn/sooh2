<?php
namespace Sooh2\BJUI\Pages;
/**
 * todo:没处理2层合并式的列表的title
 */
class EditInDlg extends \Sooh2\HTML\Page
{
    /**
     * 
     * @return \Sooh2\BJUI\Pages\EditStd
     */    
    public static function getInstance($newInstance = null) {
        return parent::getInstance($newInstance);
    }

    /**
     * @param \Sooh2\HTML\Form\Base
     * @return \Sooh2\BJUI\Pages\EditStd
     */    
    public function initForm($form)
    {
        $this->_theForm = $form;
        return $this;
    }
    /**
     *
     * @var \Sooh2\HTML\Form\Base
     */
    protected $_theForm;


    public function render()
    {
        $s = '<div class="bjui-pageContent">';
        $s .= '<div class="bs-callout bs-callout-info"><h4>修改：'.$this->title.'</h4></div>';
        $s .= $this->_theForm->render(2);
        $s .=  '</div>';
        $s .= '<div class="bjui-pageFooter"><ul><li><button type="button" class="btn-close" data-icon="close">取消</button></li>';
        $s .='<li><button type="submit" class="btn-default"  data-icon="save">保存</button></li></ul></div>';
        return $s;
    }

                        
}