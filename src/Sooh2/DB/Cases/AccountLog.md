## 设计：

可以通过流水记录的方式获知用户余额，不在单独需要一个表的字段来记录

## 基本用法

kvobj的设置，这里不重复了

初始化：

        $alog = \Sooh2\DB\Cases\AccountLog::getRecentCopy($obj);
        $alog->load();

获取余额

        $ret = $alog->getBalance();

获取帐户类型

        $alog->getAccountType();

获取历史纪录

        $alog->getHistory(where,pagesizeOrPager);

增加一条交易记录(事务模式)

        $transcationid= $alog->transactionStart(100, 'recharge', 'recharge100');
        $alog->transactionCommit(); 或者 ->transactionRollback()
        
增加一条交易记录（直插模式：直接插入一条成功记录）

        $alog->transactionDirectly(100, 'recharge', 'recharge100');

## 建库表

        CREATE TABLE `tb_accountlog_0` (
          `alUserId` varchar(36)  NOT NULL,
          `alRecordId` bigint(20) NOT NULL COMMENT '递增,记录用户的第几笔交易,',
          `alOrderType` varchar(36)  NOT NULL DEFAULT 'unset' COMMENT '单订类型（系统保留了一个rollback）',
          `alOrderId` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号（rollback时就是alRecordId）',
          `alStatus` tinyint(2) NOT NULL DEFAULT '0' COMMENT '-1：新增;0 成功; 1：回滚;2 超时回滚',
          `chg` int(11) NOT NULL DEFAULT '0' COMMENT '金额变化',
          `balance` bigint(20) NOT NULL DEFAULT '0' COMMENT '变化后的余额',
          `ymd` int(11) NOT NULL DEFAULT '0' COMMENT '时间：年月日',
          `dtCreate` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
          `rowVersion` int(11) NOT NULL DEFAULT '1' COMMENT '遵循KVObj，但改为记录这个用户的第几笔流水用了',
          index st (alStatus),
          PRIMARY KEY (alUserId,alRecordId)
        )  DEFAULT CHARSET=utf8

## 注意事项

1. 没有使用db的事务逻辑，所以根据业务情况可能需要一个辅助程序处理超时
2. 由于使用了整数记录金额，所以像人民币元请转换成分存储
3. 因具体到个人的时候交易频率不高，所以加记录的地方没做逻辑优化(需要递归找到成功记录确认余额)
4. 如果一个人连续多笔失败（超出重试次数），则无法继续交易，需要补一条成功的记录（对账记录）
