<?php
namespace Sooh2\BJUI\Pages;

/**
 * navtab，里面就一个编辑form
 *
 * @author simon.wang
 */
class EditInNav {
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
        $s .= $this->_theForm->render();
        $s .= '</div>
<div class="bjui-pageFooter">
    <ul>
        <li><button type="button" class="btn-close" data-icon="close">取消</button></li>
        <li><button type="submit" class="btn-default" data-icon="save">保存</button></li>
    </ul>
</div>';
        return $s;
    }
}
