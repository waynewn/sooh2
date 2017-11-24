<?php
namespace Sooh2\BJUI\Pages;
/**
 * todo:没处理2层合并式的列表的title
 */
class ListWithCondition extends ListStd
{
    /**
     * 
     * @return \Sooh2\BJUI\Pages\ListWithCondition
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

    protected $_navtab_options;
    protected $_id_for_phone;

    public function render()
    {
        $uri = \Sooh2\Misc\Uri::getInstance(); 
        $navid = $uri->currentModule()."-".$uri->currentController();
        
        $this->_navtab_options = "{id:'{$navid}', url:''}";

        
        // \Sooh2\Misc\ViewExt::getInstance()->initRenderType('echo');

        $s = '<div class="bjui-pageHeader" style="background-color:#fefefe; border-bottom:none;">';
        $s .= $this->_theForm->render();
        $s .='</div>';//bjui-pageHeader
        $s.= '<div class="bjui-pageContent">';
        if(!$this->_theForm->isUserRequest(null)){
            $s .= '<span>请先设置搜索条件</span>';
        }else{
            $s.=parent::render();
        }
        $s.= '</div>';
        return $s;
    }
}