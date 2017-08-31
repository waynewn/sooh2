<?php
namespace Sooh2\Crond;

/**
 * usage
 *		::getInstance()
 *      ->sleepTo(hour,minute...)
 *		->sleep(seconds,milliseconds)
 *		->reset()
 *		->timestamp(dayadd)
 *		->yesterday('Ymd')
 *		->YmdFull
 *		->Ymd
 *		......
 * @author Simon Wang <sooh_simon@163.com> 
 */
class Time
{
	/**
	 * 给定日期对应的起止时间段的时间戳
	 * <code>$r = Sooh2\Crond\Time::dayToBetween(time(), 2);</code>
	 * @param mixed $dt
	 * @return array(timestamp, timestamp)   dt>=$r[0] and dt<$r[1] 2015-03-18 00:00:00-2015-03-19 23:59:59
	 */
	public static function dayToBetween($dt,$dur=1)
	{
		if(is_numeric($dt)){
			if($dt<22151231 & $dt>18991231){
				$dt = strtotime($dt);
			}
		}else $dt = strtotime($dt);
		$dt = $dt - (date('H',$dt)*3600) - (date('i',$dt)*60) - date('s',$dt) +86399;
		return array($dt-$dur*86400+1,$dt);
	}
	private static $instance=null;
	/**
	 *
	 * @return Time
	 */	
	public static function getInstance()
	{
		$c= get_called_class();
		if(self::$instance==null){
			self::$instance=new $c;
			self::$instance->reset();
		}

		return self::$instance;
	}
	public function timestamp($dayAdd=0)
	{
		return $this->_timestamp+86400*$dayAdd;
	}
	private $_timestamp;
	/**
	 * 构建一个指定的时间，典型场景：手动执行计划任务跑某个时间的
	 * @return Time
	 */	
	public function mktime($h,$i,$s,$mOrYMD,$d=null,$y=null,$ms=0)
	{
		if($h===null)$h=$this->hour;
		if($i===null)$i=$this->minute;
		if($s===null)$s=$this->second;
		if($mOrYMD>100){
			$d=$mOrYMD%100;
			$y=floor($mOrYMD/10000);
			$mOrYMD=floor( ($mOrYMD%10000) / 100 );
		}else{
			if($mOrYMD===null)$mOrYMD=floor( ($this->YmdFull%10000) / 100 );
			if($d===null)$d=$this->YmdFull%100;
			if($y===null)$y=floor($this->YmdFull/10000);
		}
		
		$this->millisecond = substr($ms,0,3);
		$dt = mktime($h,$i,$s,$mOrYMD,$d,$y);
		$this->explodeInner($dt);
		return $this;
	}
	protected function _getTime()
	{
		return microtime(true);
	}
	/**
	 * 
	 * @return Time
	 */		
	public function reset()
	{
		list($sec,$ms) = explode('.', $this->_getTime());
		$this->explodeInner($sec);
		$this->millisecond = substr($ms,0,3);
		
		return $this;
	}
	protected function explodeInner($sec)
	{
		list($this->YmdFull, $this->ymd,$this->his,$this->hour, $this->minute,$this->second,$this->week,$this->weekday,  $this->YmdH,  $this->mdy)
				= explode(' ',date('Ymd ymd His G i s W w YmdH mdy',$sec));
		$this->_timestamp=$sec;
		$this->hour-=0;
		$this->minute-=0;
		$this->now = $this->_timestamp.'.'.$this->millisecond;
		$this->millisecond-=0;
	}
	public function yesterday($format='Ymd')
	{
		return date($format,$this->timestamp(-1));
	}
	public $mdy;
	public $millisecond = 0;
	public function isWeekend()
	{
		return $this->weekday%6==0?1:0;
	}
	public $dtArr=array();
	public function sleep($seconds, $millisecond=0)
	{
		usleep($seconds*1000000+$millisecond*1000);
		$this->reset();
	}
	public function sleepTo($h,$i,$s=0,$d=null,$m=null,$y=null)
	{
		if($d==null){
			$dt = mktime($h,$i,$s);
		}elseif($m==null){
			$dt = mktime($h,$i,$s,date('m',$this->timestamp(),$d));
		}elseif($y==null){
			$dt = mktime($h,$i,$s,$m,$d);
		}else $dt = mktime($h,$i,$s,$m,$d,$y);
		
		$n = microtime(true);
		
		if($dt>$n){
			list($sec,$mc) = explode('.',$dt-$n);
			$this->sleep($sec, substr($mc,0,3)+5);
		}
		$this->reset();
	}

	public function weekdaySameAs($chkVal)
	{
		return $chkVal%7 == $this->weekday%7;
	}

	public $YmdH;
	public $YmdFull;
	public $ymd;
	public $his;
	
	public $now;
	public $weekday;
	public $week;
	public $hour;
	public $minute;
	public $second;
	public function ymdhis()
	{
		return $this->YmdFull.sprintf('%06d',$this->his);
	}
}