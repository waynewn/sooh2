<?php
namespace Sooh2\DB;

class DBErr extends \ErrorException
{
    
    const connectError=1;
    const dbExists=2;
    const dbNotExists=4;
    const tableExists=8;
    const tableNotExists=16;
    const fieldExists=32;
    const fieldNotExists=64;
    const duplicateKey=128;
    const otherError=1073741824;
    
    public function __construct($code,$errOriginal,$lastSql) {
        $this->strLastCmd = is_string($lastSql)?$lastSql:  json_encode($lastSql);
        parent::__construct($errOriginal, $code);
        //$this->getTrace();
    }
    /**
     * fieldname of duplicated key
     * @var string
     */
    public $keyDuplicated=null;
    /**
     * description of last cmd for log
     * @var string
     */
    public $strLastCmd;
    
    //public static $maskSkipTheseError=0;
}

