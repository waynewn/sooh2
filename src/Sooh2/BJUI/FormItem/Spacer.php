<?php
namespace Sooh2\BJUI\FormItem;
/**
 * 占位用的（比如textarea后面通常需要跟这么一个）
 *
 * @author simon.wang
 */
class Spacer extends \Sooh2\HTML\Item\Base{
    public function render(){

        $s = "\n".'<label class="row-label"></label>
    <div class="row-input"></div>';
        return $s;
        
    }
    public function isConsts(){return true;}
}
