<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sooh2\DB\Myisam;

/**
 * Description of Special
 *
 * @author wangning
 */
class Special extends Cmd{
    /**
     * 
     * @param array $arrConnIni
     * @return \Sooh2\DB\Myisam\Special
     */
    public static function getInstance($arrConnIni)
    {
        $conn = \Sooh2\DB::getConn($arrConnIni);
        $guid = 'mysql@'.$conn->guid;
        if(isset(\Sooh2\DB::$pool[$guid])){
            \Sooh2\DB::$pool[$guid] = new Special();
            \Sooh2\DB::$pool[$guid]->connection = $conn;
        }
        return \Sooh2\DB::$pool[$guid];
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
