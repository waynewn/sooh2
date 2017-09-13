<?php
if(!function_exists('autoload_sooh2')){
    function autoload_sooh2($class){
        $tmp = explode('\\', $class);
        if(sizeof($tmp)==1){
            return false;
        }
        $cmp = array_shift($tmp);
        if($cmp===''){
            $cmp = array_shift($tmp);
        }
        if($cmp=='Sooh2'){
            include __DIR__.'/'.implode('/', $tmp).'.php';
            return true;
        }else{
            return false;
        }
    }
    spl_autoload_register('autoload_sooh2');
}