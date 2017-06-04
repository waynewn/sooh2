<?php
namespace Sooh2\DB\Myisam;

class KVSupport extends Broker
{
    protected $pkey;
    protected $indexes;
    protected function init()
    {
        $this->pkey = ['id'=>0];
        $this->indexes=array();
    }
    public function kvoLoad()
    {
        
    }
    public function kvoSave()
    {
    
    }
    public function kvoCreateTable()
    {
    
    }    
}

