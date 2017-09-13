<?php
namespace Sooh2\DB\Interfaces;

/**
 * 数据库链接
 *
 * @author wangning
 */
abstract class Conn {
    /**
     * 返回具体数据库使用的链接(handle)
     */
    abstract public function getConnHandle();
    abstract public function freeConnHandle();
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
     * 返回之前的数据库名
     */
    abstract public function change2DB($dbName);
    abstract public function restore2DB();
    public $dbNamePre=null;
}
