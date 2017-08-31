<?php
namespace Sooh2\HTML;

class Table
{
    /**
     * 
     * @param type $htmlid
     * @return \Sooh2\HTML\Table
     */
    public static function factory($htmlid=null)
    {
        $o = new Table;
        if(!empty($htmlid)){
            $o->htmlid=$htmlid;
        }
        return $o;
    }
    public $htmlid='';
    public $headers=array();
    public $data=null;
    public $jsonUrl;
    /**
     * 
     * @param string $title
     * @param string $fieldName
     * @param string $width
     * @param string $css
     * @return \Sooh2\HTML\Table
     */
    public function addHeader($title,$fieldName=null,$width=null,$css=null)
    {
        $col = new TableHeader;
        $tmp  = explode('.', $title);
        $col->title= array_pop($tmp);
        $col->titleGrp= array_shift($tmp);
        $col->fieldName = $fieldName?$fieldName:$title;
        $col->width = $width;
        $col->css = $css;
        $this->headers[]=$col;
        return $this;
    }
    /**
     * 
     * @param string $url
     * @return \Sooh2\HTML\Table
     */
    public function initJsonDataUrl($url)
    {
        $this->jsonUrl = $url;
        return $this;
    }
}

