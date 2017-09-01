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
    /**
     * 进入指定数据库
     * @param string $dbName
     * @return string $dbname 成功返回 dbname
     */
    abstract public function change2DB($dbName);
    /**
     * 回到上一个进入的数据库
     * @return mixed 成功返回回到了哪个数据库，null 表示没有上一级了
     */
    abstract public function restore2DB();
    public $dbNamePre=null;
}
