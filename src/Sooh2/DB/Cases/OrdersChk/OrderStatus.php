<?php
namespace Sooh2\DB\Cases\OrdersChk;

/**
 * 订单常用状态
 * （状态匹配，指必须存在记录且状态一致）
 * @author wangning
 */
class OrderStatus {
    const success = 'ok';      //完全成功的交易 - 状态需匹配
    const refused = 'refused'; //拒绝的交易（失败） - 状态不需匹配
    const failed = 'failed';   //失败的交易（失败） - 状态不需匹配
    const prepare = 'prepare'; //进行中的交易（不用计算金额变动） - 状态不需匹配
    const frose   = 'frose';   //进行中的交易（交易中有冻结状态，目前处于冻结状态） - 状态需匹配
    const unknown ='unknown';  //新增未知状态，需要完善代码
}
