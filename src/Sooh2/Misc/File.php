<?php
namespace Sooh2\Misc;

/**
 * 文件操作常用功能类
 *
 * @author wangning
 */
class File {
    /**
     * 获取某个目录下文件列表（只包含子路径名）
     * @param string $path 搜索哪个路径，默认深入搜索一级子目录
     * @param int $maxdepth 遍历深度
     * @return array 
     */
    public static function fileInDir($path,$maxdepth=1)
    {
        if(!is_dir($path)){
            throw new \ErrorException("$path is not a dir");
        }
        $files = array();
        $subdirs = array();
        
        $dh = opendir($path);
        if(!$dh){
            throw new \ErrorException("opendir:$path failed");
        }
        while(false!==($subdir=  readdir($dh))){
                if($subdir[0]!=='.'){
                    if(is_dir($path.'/'.$subdir)){
                        $subdirs[]=$subdir;
                    }else{
                        $files[]=$subdir;
                    }
                }
        }
        closedir($dh);
        
        if($maxdepth>0){
            foreach ($subdirs as $subdir){
                $tmp = self::fileInDir($path.DIRECTORY_SEPARATOR.$subdir,$maxdepth-1);
                foreach($tmp as $s){
                    $files[] = $subdir.DIRECTORY_SEPARATOR.$s;
                }
            }
        }
        return $files;
    }
    
    /**
     * 获取某个目录下子目录的列表（只包含子路径名）
     * @param string $path 搜索哪个路径，默认深入搜索一级子目录
     * @param int $maxdepth 遍历深度
     * @return array
     */
    public static function dirInDir($path,$maxdepth=1)
    {
        if(!is_dir($path)){
            throw new \ErrorException("$path is not a dir");
        }
        $subdirs = array();
        
        $dh = opendir($path);
        if(!$dh){
            throw new \ErrorException("opendir:$path failed");
        }
        while(false!==($subdir=  readdir($dh))){
                if($subdir[0]!=='.'){
                    if(is_dir($path.'/'.$subdir)){
                        $subdirs[]=$subdir;
                    }
                }
        }
        closedir($dh);
        $ret = $subdirs;
        if($maxdepth>0){
            foreach ($subdirs as $subdir){
                $tmp = self::dirInDir($path.DIRECTORY_SEPARATOR.$subdir,$maxdepth-1);
                foreach ($tmp as $s){
                    $ret[]=$subdir.DIRECTORY_SEPARATOR.$s;
                }
            }
        }
        return $ret;
    }
    /**
     * 获取某个目录下php文件名对应完整路径的列表（AA\BB => path/AA/BB.php, 键值应该是类名，值是可加载的包含完整路径的文件路径）
     * @param string $path 搜索哪个路径，默认深入搜索一级子目录
     * @param int $maxdepth 遍历深度
     * @return array  array(AA\BB => path/AA/BB.php)
     */
    public static function phpclassInDir($path,$maxdepth=1)
    {
        $realpath = realpath($path);
        $fs = self::fileInDir($realpath,$maxdepth);
        $ret = array();
        foreach($fs as $f){
            if(substr($f, -4)=='.php'){
                $ret[str_replace(DIRECTORY_SEPARATOR,'\\',substr($f,0,-4))]=$realpath.DIRECTORY_SEPARATOR.$f;
            }
        }
        return $ret;
    }
    


}
