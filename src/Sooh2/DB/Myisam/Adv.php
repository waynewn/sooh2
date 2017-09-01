<?php
namespace Sooh2\DB\Myisam;



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

    
	public function addLog($obj,$fields)
	{
		$this->connection->getConnection();
        $this->_lastCmd = 'INSERT DELAYED into '
            .$this->fmtObj($obj, $this->connection->dbName)
            .' set '.$this->buildFieldsForUpdate($fields,null);

        $this->exec(array($this->_lastCmd));
        $this->skipErrorLog(null);
        $insertId = mysqli_insert_id($this->connection->connected);
        
        return true;
	}
    
    
}

