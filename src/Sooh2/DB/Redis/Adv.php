<?php
namespace Sooh2\DB\Redis;



class Adv extends Cmd 
{
	/**
	 * @return \Sooh2\DB\Myisam\Adv
	 */
	public static function getInstance($ini)
	{
		$c = \Sooh2\DB::getConn($ini);
		$guid = $c->guid;
		if(!isset(\Sooh2\DB::$otherPool[$guid])){
			\Sooh2\DB::$otherPool[$guid] = new Adv;
			\Sooh2\DB::$otherPool[$guid]->connection = $c;
		}
		return \Sooh2\DB::$otherPool[$guid];
	}
    
	public function getStr($key,$where=null)
	{
		$this->connection->getConnection();
		$allkeys = $this->fmtObj($key,$where);
		if(sizeof($allkeys)==1){
			return $this->exec(array(['get',$allkeys[0]]));
		}else{
			$ret = array();
			foreach($allkeys as $k){
				$ret[$k]=$this->exec(array(['get',$allkeys[0]]));
			}
			return $ret;
		}
	}
    
	public function setStr($key,$val)
	{
		$this->connection->getConnection();
		$allkeys = $this->fmtObj($key);
		if(is_array($val)){
			$val = json_encode($val);
		}
		return $this->exec(array(['get',$allkeys[0],$val]));
	}
	
	public function setExpire($key,$secondsExpired)
	{
		$this->connection->getConnection();
		$allkeys = $this->fmtObj($key,null);
		return $this->exec(array(['expireAt',$allkeys[0],time()+$secondsExpired]));
	}
	public function dropKey($key,$where=null)
	{
		$this->connection->getConnection();
		$allkeys = $this->fmtObj($key,$where);
		if(sizeof($allkeys)==1){
			$this->exec(array(['delete',$allkeys[0]]));
		}else{
			foreach($allkeys as $k){
				$this->exec(array(['delete',$allkeys[0]]));
			}
		}
	}
}

