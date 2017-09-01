<?php
namespace Sooh2\DB\Myisam;

class Conn extends \Sooh2\DB\Interfaces\Conn {
    /**
     * 
     * @return \Sooh2\DB\Interfaces\Conn
     * @throws \Sooh2\DB\DBErr
     */
    public function getConnection()
    {
        if($this->connected){
            return $this->connected;
        }
        try{
            \Sooh2\Misc\Loger::getInstance()->sys_trace("TRACE: myisam connecting todo(usedb)");
            $this->connected=mysqli_connect($this->server,$this->user,$this->pass,null,$this->port);
            if(!$this->connected){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, mysqli_connect_errno().":".mysqli_connect_error(), "");
            }
            $this->change2DB($this->dbNameDefault);
            if(!empty($this->charset)){
                mysqli_query($this->connected, 'set names '.$this->charset);
            }
            return $this->connected;
        }catch (\ErrorException $e){
            throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage()." when try connect to {$this->server} by {$this->user}", "");
        }

    }
    public function disConnect()
    {
        if($this->connected){
            mysqli_close($this->connected);
            $this->connected=false;
        }
    }
    public function change2DB($dbName)
    {
        $this->dbNamePre = $this->dbName;
        if($this->connected===false){
            $this->getConnection();
        }
        mysqli_select_db($this->connected, $dbName);
        return $this->dbName = $dbName;
        if(mysqli_errno($this->connected)){
            throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, " try use db $dbName failed, missing or no-rights?", "");
        }
    }
    public function restore2DB()
    {
    	if($this->dbNamePre){
    		$to=$this->dbNamePre;
    		$this->change2DB($to);
    		$this->dbNamePre=null;
    		return $to;
    	}else{
    		return null;
    	}
        
    }
}

