<?php
namespace Sooh2\BJUI\Pages;
/**
 * todo:没处理2层合并式的列表的title
 */
class ListWithPannel extends ListStd
{
    /**
     * 
     * @return \Sooh2\BJUI\Pages\ListWithPannel
     */    
    public static function getInstance($newInstance = null) {
        return parent::getInstance($newInstance);
    }

    /**
     * @return \Sooh2\BJUI\Pages\EditStd
     */    
    public function initPannel($pannel)
    {
        $this->_thePannel = $pannel;
        return $this;
    }
    /**
     *
     * @var 
     */
    protected $_thePannel;

    protected $_navtab_options;
    protected $_id_for_phone;

    public function render()
    {
        $uri = \Sooh2\Misc\Uri::getInstance(); 
        $navid = $uri->currentModule()."-".$uri->currentController();
        
        $this->_navtab_options = "{id:'{$navid}', url:''}";

        
        // \Sooh2\Misc\ViewExt::getInstance()->initRenderType('echo');

        $s = '<div class="bjui-pageHeader" style="background-color:#fefefe; border-bottom:none;">';
        $s .= $this->_thePannel;
        $s .='</div>';//bjui-pageHeader
        $s.= '<div class="bjui-pageContent">';
        $s.=parent::render();
        $s.= '</div>';
        return $s;
    }
}