<?php
namespace Sooh2\Misc;

class Ini
{
    /**
     * 获取或设置Ini 
     *   $newInstance_or_iniPath = null： 获取当前的实例
     *   $newInstance_or_iniPath = 配置文件路径： 初始化为使用sooh基本loger类
     *   $newInstance_or_iniPath = Ini实例： 初始化为使用自定义ini实例
     * @param mixed $newInstance_or_iniPath
     * @return \Sooh2\Misc\Ini
     */
    public static function getInstance($newInstance_or_iniPath=null)
    {
        if($newInstance_or_iniPath!=null){
            if(is_string($newInstance_or_iniPath)){
                self::$_instance = new Ini();
                self::$_instance->basePath=$newInstance_or_iniPath;
            }else{
                self::$_instance = $newInstance_or_iniPath;
            }
        }elseif(empty(self::$_instance)){
            $err =  new \ErrorException('base path of configure files not given');
            error_log($err->getMessage()."\n".$err->getTraceAsString());
            throw $err;
        }
        return self::$_instance;
    }
    protected static $_instance;
    protected $basePath;
    protected $loaded = array();
    protected $runtime=array();
    /**
     * 设置运行时参数
     * @param string $k
     * @param mixed $v
     * @return Ini
     */
    public function setRuntime($k,$v)
    {
        $this->runtime[$k]=$v;
        return $this;
    }
    /**
     * 获取运行时参数
     * @param string $k
     * @return mixed
     */
    public function getRuntime($k)
    {
        if(isset($this->runtime[$k])){
            return $this->runtime[$k];
        }else{
            return null;
        }
    }
    /**
     * 获取serverid
     * @return int
     */
    public function getServerId()
    {
        if($this->runtime['serverId']){
            return $this->runtime['serverId']-0;
        }else{
            return 0;
        }
    }
    /**
     * 获取预定义参数
     * @param string $k
     * @throws \ErrorException
     * @return mixed
     */
    public function getIni($k)
    {
        $r = explode('.', $k);
        $f = array_shift($r);
        if(!isset($this->loaded[$f])){
            $file = $this->basePath.'/'.$f.'.ini';
            if(!is_file($file)){
                throw new \ErrorException('empty ini file found:'.$f.'.ini');
            }else{
                $this->loaded[$f]=parse_ini_string(file_get_contents($file),true);
            }
        }
        $tmp = $this->loaded[$f];
        foreach($r as $i){
            if(isset($tmp[$i])){
                $tmp = $tmp[$i];
            }else{
                return null;
            }
        }
        return $tmp;
    }
    /**
     * 设置Ini, 注意以下两点：
     * 1）如果存在相应的配置文件，请确认文件已被加载过再调用此函数
     * 2）key最大深度4层
     */
    public function setIni($k,$v)
    {
        $ks = explode('.', $k);
        switch(sizeof($ks)){
            case 1:
                $this->loaded[$ks[0]]=$v;
                break;
            case 2:
                $this->loaded[$ks[0]][$ks[1]]=$v;
                break;
            case 3:
                $this->loaded[$ks[0]][$ks[1]][$ks[2]]=$v;
                break;
            case 4:
                $this->loaded[$ks[0]][$ks[1]][$ks[2]][$ks[3]]=$v;
                break;
            default:
                throw new \ErrorException('max-depth=4 in ini->setIni');
        }
    }
    /**
     * 获取预定义文字串
     * @param string $k
     * @throws \ErrorException
     * @return mixed
     */
    public function getLang($k)
    {
        $r = explode('.', $k);
        $f = array_shift($r);
        $id = array_shift($r);
        if(sizeof($r)){
            throw new \ErrorException('one deep level support only in getLang');
        }
        if(!isset($this->loaded['LANG_'.$f])){
            $langPath = $this->getIni('application.langFullPath');
            $lang = $this->getIni('application.language');
            $file = $langPath.'/'.$f.'/'.$lang.'.txt';
            if(!is_file($file)){
                throw new \ErrorException('empty lang file found:'.$f.'.'.$lang);
            }else{
                $this->loaded["LANG_".$f]=parse_ini_file($file);
                
            }
        }
        if(isset($this->loaded["LANG_".$f][$id])){
            return $this->loaded["LANG_".$f][$id];
        }else{
            Loger::getInstance()->sys_warning('getLang('.$k.') failed');
            return $id;
        }
    }
}