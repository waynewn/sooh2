<?php
namespace Sooh2;

class DB
{
    /**
     * 获取默认的行记录版本id字段名（默认：rowVersion，可以通过define('DBROW_VERFIELD')来指定）
     */
    public static function version_field()
    {
        if(defined('DBROW_VERFIELD')){
            return DBROW_VERFIELD;
        }else{
            return 'rowVersion';
        }
    }
    /**
     * check param, then create new put into OR get exists from pool
     * @param mixed $arrConnection
     * @throws \ErrorException on any parse-error found
     * @return \Sooh2\DB\Interfaces\Conn
     */
    public static function getConn($arrConnection)
    {
        if(is_string($arrConnection)){
            $arrConnection = json_decode($arrConnection,true);
        }
        if(is_array($arrConnection)){
            if(!empty($arrConnection['dbType'])){
                $c = "\\Sooh2\\DB\\".$arrConnection['dbType']."\\Conn";
                $tmp = new $c();
                $tmp->dbType = $arrConnection['dbType'];
            }else{
                throw new \ErrorException('missiong dbType in db-connection-ini');
            }
            if(!empty($arrConnection['server'])){
                $tmp->server = $arrConnection['server'];
            }else{
                throw new \ErrorException('missiong server in db-connection-ini');
            }
            if(empty($arrConnection['port'])){
                if($arrConnection['port']===0){
                    $tmp->port = $arrConnection['port'];
                }else{
                    throw new \ErrorException('invalid port in db-connection-ini');
                }
            }else{
                if(is_numeric($arrConnection['port'])){
                    $tmp->port = $arrConnection['port'];
                }else{
                    throw new \ErrorException('invalid port in db-connection-ini');
                }
            }
            if(!empty($arrConnection['user'])){
                $tmp->user = $arrConnection['user'];
            }else{
                throw new \ErrorException('missiong user in db-connection-ini');
            }
            $tmp->guid = $tmp->user.'@'.$tmp->server.':'.$tmp->port;
            if(isset(self::$connPool[$tmp->guid])){
                return self::$connPool[$tmp->guid];
            }
            if(!empty($arrConnection['charset'])){
                $tmp->charset = $arrConnection['charset'];
            }
            if(!empty($arrConnection['pass'])){
                $tmp->pass = $arrConnection['pass'];
            }else{
                throw new \ErrorException('missiong pass in db-connection-ini');
            }
            $tmp->dbNameDefault = $arrConnection['dbName'];
            //error_log("wnwww 9:". json_encode($arrConnection)."#".var_export($arrConnection,true));
            return self::$connPool[$tmp->guid]=$tmp;
        }else{
            if(!empty($arrConnection->guid)){
                
                //////////////////////////??????????????????????????
                return self::$connPool[$tmp->guid];
            }else{
                throw new \ErrorException('param-given is not a connection class, configuration error?'.var_export($tmp,true));
            }
        }
    }
    
    
    /**
     * check param, then create new put into OR get exists from pool
     * @param mixed $arrConnection
     * @throws \ErrorException on any parse-error found
     * @return \Sooh2\DB\Interfaces\DB
     */
    public static function getDB($arrConnection)
    {
        $tmp = self::getConn($arrConnection);
        if(!isset(self::$pool[$tmp->guid])){
            $c = "\\Sooh2\\DB\\".$tmp->dbType."\\Broker";
            if(!class_exists($c,false)){
                include __DIR__."/DB/".$tmp->dbType."/Broker.php";
            }

            $db = new $c;
            $db->connection = $tmp;
            self::$pool[$tmp->guid]=$db;
        }
        return self::$pool[$tmp->guid];
    }
    
    /**
     * @deprecated 
     * check param, then create new put into OR get exists from pool
     * @param mixed $arrConnection
     * @throws \ErrorException on any parse-error found
     * @return \Sooh2\DB\Interfaces\DB
     */
    public static function getConnection($arrConnection)
    {
        return self::getDB($arrConnection);
//        \Sooh2\Misc\Loger::getInstance()->sys_warning('deprecated use getDB() instead ');
//        if(is_string($arrConnection)){
//            $arrConnection = json_decode($arrConnection,true);
//        }
//        if(is_array($arrConnection)){
//            $tmp = new DB();
//            if(!empty($arrConnection['dbType'])){
//                $tmp->dbType = $arrConnection['dbType'];
//            }else{
//                throw new \ErrorException('missiong dbType in db-connection-ini');
//            }
//            if(!empty($arrConnection['server'])){
//                $tmp->server = $arrConnection['server'];
//            }else{
//                throw new \ErrorException('missiong server in db-connection-ini');
//            }
//            if(empty($arrConnection['port'])){
//                if($arrConnection['port']===0){
//                    $tmp->port = $arrConnection['port'];
//                }else{
//                    throw new \ErrorException('invalid port in db-connection-ini');
//                }
//            }else{
//                if(is_numeric($arrConnection['port'])){
//                    $tmp->port = $arrConnection['port'];
//                }else{
//                    throw new \ErrorException('invalid port in db-connection-ini');
//                }
//            }
//            if(!empty($arrConnection['user'])){
//                $tmp->user = $arrConnection['user'];
//            }else{
//                throw new \ErrorException('missiong user in db-connection-ini');
//            }
//            $tmp->guid = $tmp->user.'@'.$tmp->server.':'.$tmp->port;
//            if(!empty($arrConnection['charset'])){
//                $tmp->charset = $arrConnection['charset'];
//            }
//            if(!empty($arrConnection['pass'])){
//                $tmp->pass = $arrConnection['pass'];
//            }else{
//                throw new \ErrorException('missiong pass in db-connection-ini');
//            }
//            $tmp->dbNameDefault = $arrConnection['dbName'];
//
//            if(isset(self::$pool[$tmp->guid])){
//                $cmp = self::$pool[$tmp->guid]->connection;
//                if($cmp->dbNameDefault === $tmp->dbNameDefault && $cmp->pass === $tmp->pass){
//                    return self::$pool[$tmp->guid];
//                }else{
//                    throw new \ErrorException('finding different pass or dbname for same db-connection');
//                }
//            }else{
//                $c = "\\Sooh2\\DB\\".$tmp->dbType."\\Broker";
//                if(!class_exists($c,false)){
//                    include __DIR__."/DB/".$tmp->dbType."/Broker.php";
//                }
//                $db = new $c;
//                $db->connection = $tmp;
//                return self::$pool[$tmp->guid]=$db;
//            }
//        }else{
//            if(!empty($arrConnection->connection->guid)){
//                //////////////////////////??????????????????????????
//                return $arrConnection;
//            }else{
//                throw new \ErrorException('param-given is not a connection class, configuration error?');
//            }
//        }
    }
    /**
     * release one or all connections
     * 
     * @param string $guid connetion-guid
     * @return num of connections released
     */
    public static function free($guid=null)
    {
        if($guid!==null){
            if(isset(self::$pool[$guid])){
                self::$pool[$guid]->disconnect();
                unset(self::$pool[$guid]);
                return 1;
            }else{
                return 0;
            }
        }else{
            $ks = array_keys(self::$pool);
            foreach($ks as $k){
                self::$pool[$k]->disconnect();
                unset(self::$pool[$k]);
            }
            return sizeof($ks);
        }
    }
    public static $pool = array();
    protected static $connPool=array();
    public $dbType;
    public $guid;
    public $server;
    public $port;
    public $user;
    public $pass;
    public $dbNameDefault;
    public $dbName;
    public $connected=null;
}

