<?php
namespace Sooh2\DB\Interfaces;

/**
 * 数据库链接
 *
 * @author wangning
 */
abstract class Conn {
    abstract public function getConnection();
    abstract public function disConnect();
    public $dbType;
    public $guid;
    public $server;
    public $port;
    public $user;
    public $pass;
    public $dbNameDefault;
    public $dbName;
    public $connected=null;
    abstract public function change2DB($dbName);
    abstract public function restore2DB();
    public $dbNamePre=null;
}
