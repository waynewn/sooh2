<?php
namespace Sooh2\DB\Interfaces;

interface DBReal extends DB
{
    public function connect();
    public function disconnect();
}

