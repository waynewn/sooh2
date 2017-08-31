<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sooh2\Messager\Interfaces;

/**
 *
 * @author simon.wang
 */
interface MsgSendLog {
    public static function getCopy($logid);
    public static function createNew($title,$content,$user,$ways,$evtmsgid);
    public function getUsers();
    public function getContent();
    public function getTitle();
    public function setResult($wayid,$ret);
    /**
     * @retrun mix 是否成功加载了指定的记录
     */
    public function load();
    /**
     * @return bool 是否成功
     */
    public function saveToDB();
    public function freeme();
}
