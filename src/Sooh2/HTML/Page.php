<?php
namespace Sooh2\HTML;
class Page
{
    public $title;
    public $unique_key; //页面的唯一class,防止css,js污染
    /**
     * 
     * @param type $newInstance
     * @return \Sooh2\HTML\Page
     */
    public static function getInstance($newInstance=null)
    {
        if($newInstance!==null){
            self::$_instance = $newInstance;
        }elseif(self::$_instance==null){
            $c = get_called_class();
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    protected static $_instance = null;
    /**
     * 
     * @param string $title
     * @return \Sooh2\HTML\Page
     */
    public function init($title)
    {
        $this->title=$title;
        return $this;
    }
    public function render(){}

    public function setUniqueKey($str){
        $this->unique_key = $str;
        return $this;
    }
}