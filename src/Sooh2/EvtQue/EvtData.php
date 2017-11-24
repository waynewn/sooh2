<?php
namespace Sooh2\EvtQue;

/**
 * 事件的数据结构
 *
 * @author simon.wang
 */
class EvtData {
    public $evtId;
    public $objId;
    public $userId;
    public $args;
    public function toStringDetail()
    {
        return "evt:{$this->evtId};obj:{$this->objId};u:{$this->userId};args:{$this->args}";
    }
}
