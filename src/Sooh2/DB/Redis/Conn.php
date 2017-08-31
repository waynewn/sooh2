<?php
namespace Sooh2\DB\Redis;

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
        if(!$this->connection->connected){
            try{
                \Sooh2\Misc\Loger::getInstance()->sys_trace("TRACE: Redis connecting");
                $this->connection->connected=new \Redis();
                
                $this->connection->connected->connect($this->connection->server, $this->connection->port);
                $this->connection->connected->auth($this->connection->pass);

                if(!$this->connection->connected){
                    throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, "connect to redis-server {$this->connection->server}:{$this->connection->port} failed", "");
                }
                $this->connection->dbName = $this->connection->dbNameDefault - 0;
                if($this->connection->dbName){
                    $this->connection->connected->select($this->connection->dbName);
                }
            }catch (\Exception $e){
                throw new \Sooh2\DB\DBErr(\Sooh2\DB\DBErr::connectError, $e->getMessage()." when try connect to {$this->connection->server} by {$this->connection->user}", "");
            }
        }

    }
    public function disConnect()
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
            if(!$this->connection->connected){
                $this->connect();
            }
            $this->exec(array(array('select',$dbName)));
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

