<?php
namespace Sooh2\Misc;
class Uri
{
    /**
     * 发布子路径，http://host/发布子路径/module/ctrl/action
     * @var string
     */
    protected $curBaseDir = false;
    protected $curModule = 'default';
    protected $curController = 'index';
    protected $curAction = 'index';
    /**
     * bool   false:   代表目录模式，即 http://host/m/c/a
     * string varname: 代表单变量模式，即 http://host/?varname=m/c/a
     * array  [m,c,a]: 代表使用一组变量指定， 即 http://host/?m=m&c=c&a=a
     * @var mixed 
     */
    protected $routeByVar = '__';
    protected $indexFilename='index.php';
    /**
     * 在当前module/ctrl/action 的基础上获取新的url（不指定后面mca参数的情况下，使用当前值）
     * @param array $args 其它参数
     * @param string $actNew
     * @param string $ctrlNew
     * @param string $modNew
     * @throws \ErrorException
     * @return string
     */
    public function uri($args=null,$actNew=null,$ctrlNew=null,$modNew=null)
    {
        if($actNew==null)$actNew= $this->curAction;
        if($ctrlNew==null)$ctrlNew=$this->curController;
        if($modNew==null)$modNew= $this->curModule;
        if($args==null){
            $args = array();
        }
        $url = str_replace('//', '/', '/'.$this->curBaseDir.'/');
        if(is_array($this->routeByVar)){
            $args[$this->routeByVar[0]]=$modNew;
            $args[$this->routeByVar[1]]=$ctrlNew;
            $args[$this->routeByVar[2]]=$actNew;
            $url = $url.$this->indexFilename.'?'.http_build_query($args);
        }elseif (is_string($this->routeByVar)){
            $args[$this->routeByVar]="$modNew/$ctrlNew/$actNew";
            $url = $url.$this->indexFilename.'?'.http_build_query($args);
        }else{
            $url = $url."$modNew/$ctrlNew/$actNew";
            foreach ($args as $k=>$v){
                $url.= '/'.$k.'/'.urlencode($v);
            }
        }
        return $url;
    }
    public function currentModule()
    {
        return $this->curModule;
    }
    public function currentController()
    {
        return $this->curController;
    }
    public function currentAction()
    {
        return $this->curAction;
    }
    /**
     * 同uri，区别是，变量值还原成 {str}的格式，即args 支持 [ 'k'=>'{toBeReplace}' ]
     * @param array $args
     * @param string $actNew
     * @param string $ctrlNew
     * @param string $modNew
     * @return string
     */
    public function uriTpl($args=null,$actNew=null,$ctrlNew=null,$modNew=null)
    {
        $uri = $this->uri($args,$actNew,$ctrlNew,$modNew);
        return str_replace(array('%7B','%7D'),array('{','}'),$uri);
    }

    protected static $_instance=null;
    /**
     * 获取或设置当前实例
     * @param \Sooh2\Misc\Uri $newInstance
     * @return \Sooh2\Misc\Uri
     */
    public static function getInstance($newInstance = null){
        if($newInstance === null){
            if(self::$_instance===null){
                self::$_instance = new Uri();
            }
        }else{
            self::$_instance = $newInstance;
        }
        return self::$_instance;
    }
    /**
     * 设置当前请求的 module,controller,action 作为默认值
     * @param string $module
     * @param string $ctrl
     * @param string $action
     * @return \Sooh2\Misc\Uri
     */
    public function initMCA($module,$ctrl,$action)
    {
        $this->curModule = $module;
        $this->curController = $ctrl;
        $this->curAction = $action;
        return $this;
    }
    /**
     * 设置工作模式
     * @param string $baseDir  发布的子目录
     * @param mixed $varMode  工作模式（false:目录模式，字段名字符串:单一路由变量模式，mca三元素数组：三个变量指定mca）
     * @param string $filename  处理程序入口文件名
     * @return \Sooh2\Misc\Uri
     */
    public function init($baseDir,$varMode=false,$filename='index.php')
    {
        $this->curBaseDir=trim($baseDir,'/');
        $this->routeByVar = $varMode;
        $this->indexFilename=$filename;
        return $this;
    }
}