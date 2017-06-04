<?php
namespace Sooh2\Yaf;
/**
 * 扩展的支持模板选择的view,
 * 使用此类需要  ini->setRuntime('viewRenderType','www');
 * 如果是jsonp，还需要设置 ini->setRuntime('nameJsonP','www');
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Viewext extends \Yaf_View_Simple{


	/**
	 * 是否禁用默认的HTML头和尾
	 * @var boolean
	 */
	public static $bodyonly=false;
	private $renderStart=false;

	public function render ( $strTpl , $arrTplVars=null)
	{
		//$this->preRender();
		if(is_array($arrTplVars)){
		    $tmp = array_merge($this->_tpl_vars,$arrTplVars);
		}else{
		    $tmp = $this->_tpl_vars;
		}
		$ret = \Sooh2\Misc\ViewExt::onFrameworkRender($tmp);
		if($ret===false){
		    return parent::render(\Sooh2\Misc\ViewExt::fixTplFilename($strTpl) , $arrTplVars);
		}else{
		    return '';
		}
	}
	public function display (  $strTpl , $tpl_vars =array() )
	{
	    return parent::display(\Sooh2\Misc\ViewExt::fixTplFilename($strTpl),$tpl_vars);
	}
	
	public function getScriptPath()
	{
	    return \Sooh2\Misc\ViewExt::fixTplFilename(parent::getScriptPath());
	}
	
	public function setScriptPath ( $strTpl )
	{
	    return parent::setScriptPath(\Sooh2\Misc\ViewExt::fixTplFilename($strTpl));
	}
	
	/**
	 * 渲染输出inc目录下的指定文件
	 * @param string $part
	 * @return string
	 */
	public function renderInc($part)
	{
		return $this->render(VIW_INC_PATH.$part.'.phtml');
	}
	protected $headParts=array();
	/**
	 * 追加html的head部分的内容
	 * @param string $str
	 * @return string
	 */
	public function htmlHeadPart($str=null)
	{
		if($str==null){
			return $this->headParts;
		}else{
			$this->headParts[]=$str;
		}
	}
}
