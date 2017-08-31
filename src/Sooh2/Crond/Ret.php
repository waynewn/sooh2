<?php
namespace Sooh2\Crond;
/**
 * 常用的定时任务状态集
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Ret {
	public $total=null;
	public $newadd=null;
	public $newupd=null;
	public $user=null;
	public $timeFromTo=null;
	public $failed=null;
	public function toString($prefix='')
	{
		$ret= $prefix;
		if($this->failed!==null)$ret.=' failed:'.$this->failed;
		if($this->timeFromTo!==null)$ret.=' FromTo:'.$this->timeFromTo;
		if($this->total!==null)$ret.=' total:'.$this->total;
		if($this->newadd!==null)$ret.=' add:'.$this->newadd;
		if($this->newupd!==null)$ret.=' upd:'.$this->newupd;
		if($this->user!==null)$ret.=' usr:'.$this->user;
		if(empty($ret))$ret='ok';
		return $ret;
	}
}
