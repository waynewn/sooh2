<?php
namespace Sooh2\DB\Mongodb;

class Conn extends \Sooh2\DB\Interfaces\Conn {
    /**
     * 
     * @return \Sooh2\DB\Interfaces\Conn
     * @throws \Sooh2\DB\DBErr
     */
    public function getConnHandle()
    {
        if($this->connected){
            return $this->connected;
        }
        if(!$this->connected){
            try{
                \Sooh2\Misc\Loger::getInstance()->sys_trace("TRACE: Mongo connecting");
                if(!empty($this->user)){
                    $str = "{$this->user}:{$this->pass}@";
                }else{
                    $str = '';
                }
                $this->connected=new \MongoDB\Driver\Manager("mongodb://{$str}{$this->server}:{$this->port}");

                if(!$this->connected){
                    throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, "connect to mongo-server {$this->server}:{$this->port} failed", "");
                }
                $this->dbName = null;
                if($this->dbName){
                    $this->connected->selectDB($this->dbName);
                }
            }catch (\Exception $e){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage()." when try connect to {$this->server} by {$this->user}", "");
            }
        }

    }
    public function freeConnHandle()
    {
        if($this->connected){
            $this->connected->close();
            $this->connected=false;
        }
    }
    public function change2DB($dbName)
    {
        $this->dbNamePre = $this->dbName;
        try{
            if(!$this->connected){
                $this->getConnHandle();
            }
            $this->selectDB($dbName);
            $this->dbName = $dbName;
        }catch (\ErrorException $e){
            throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::dbNotExists, $e->getMessage(), "");
        }
    }
    public function restore2DB()
    {
        return $this->change2DB($this->dbNamePre);
    }
}

