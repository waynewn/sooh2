# KVObj

## 1.设计目标

1. 针对nosql（Key-Val），提供一套封装，支持分表分库分服务器，同样忽略数据库类型

注意：

1. 不同系统可以支持的一次处理用户的最大数不一致，请在外部调用前用最小集把用户分组好（建议100为上限），内部就一股脑发送了
2. 没做成网关，虽然有日志，但调用后同步开始发送流程。如果需要异步受理再处理发送，请自行编写相关代码。

## 2.使用方式

首先是准备好通道配置，各个通道的配置格式，请参看实现的类的前面的说明，比如 Email\\SmtpSSL里写的就是“需要配置格式：user=xxx&pass=xxxx&server=smtp.exmail.qq.com[&port=465]”

### 2.1最简单的直接发送（无其他任何依赖，无模板，无日志）

        \Sooh2\Messager\Email\SmtpSSL::getInstance('user=yunwei@xyz.com&pass=123456&server=smtp.exmail.qq.com')
        ->sendTo('wangning@zhangyuelicai.com', 'tet测试123423', 'tet测试')

### 2.2系统工作模式（ 有模板，支持多通道同时发送，开启记录日志）

		Broker或Broker子类::getInstance()
		->sendEvtMsg('register', $uid_or_uidArray, ['{replaceFrom}' => $replaceTo]);

这段代码实现了：读取出register对应的配置（消息模板，需要发送到哪些通道），用replace数组进行消息模板的替换，然后，实例化需要发送的通道的类，依次发送。

细心的你可能会发现，样例中用的uid，不是phone，不是email。虽然Broker默认直接使用该字段调用发送，但内部支持根据uid和通道获取实际需要的字段（getUserForSender方法）。

要使上述代码能工作，需要准备的配置环境有：

** A)Messager.Ini 定义了系统支持的通道和配置参数： ** 

(Broker类通过getSenderCtrl()方法获取通道类，配置文件的格式是通过Misc\\Ini读取的，根据需要替换)

		[msg]
		name = '站内信'
		class = "\\Prj\\InnerMsg"
		ini = "xxxxxx"

		[email]
		name = '邮件'
		class = "\\Sooh2\\Messager\\Email\\SmtpSSL"
		ini = "user=xxx&pass=xxxx&server=smtp.exmail.qq.com"

** B）准备消息模板 **

默认的消息模板是 KVObj 的 子类，相关配置参看 [KVObj的说明](../DB/KVOBJ.md)
数据库里记录了模板id（可以兼事件标识，消息标题模板，消息内容模板，需要往哪些通道发送）

** C)如果需要记录发送日志 **

如果要发送记录入库的能力，需要使用BrokerWithLog（或其子类）代替Broker类。BrokerWithLog类是Broker的基础上增加发送记录入库能力的类，其他方面没有区别。

默认也使用了KVObj的类记录日志，如果需要换成你自己的日志记录类，覆盖getLogerClassname()

### 3.1 依赖 

- \Sooh2\Misc\Loger 会tracelog所有执行的命令语句
- \Sooh2\Misc\Ini （KVObj使用其获取配置）

### 3.2 重点系统类&常量 

| 类名              | 说明
| ----------------  | ---------------------------------------------------------
| KVObj 类          | kv式的数据操作封装
| KVObjRW 类        | 读写分离的封装


### 3.3 重点函数说明 

基本用法主要有：
addRecord   失败要么异常，要么返回false；成功：如果支持自增字段，返回自增结果，否则返回true
updRecords  失败要么异常，要么返回false；成功：如果支持变动记录数，返回变更记录数量，否则返回true
delRecord   失败要么异常，要么返回false；成功：如果支持变动记录数，返回变更记录数量，否则返回true
getOne
getPair
getRecord
getRecords
getRecordCount

### 3.4 默认提供的类需要的相关配置 sql

    无



## 其它（场景,注意事项）

*记录版本号字段：*

使用时通过自增（update verid加一  where verid=xxx），用于实现行级乐观锁。默认字段名rowVersion，可以通过 define(DBROW_VERFIELD,'重新指定')

*数组数据值*

不同数据库存取类型对数组的处理不太一样

- redis 可以直接存取
- mysql 保存时会转换成json，读出时候不会自动还原

DB 不依赖 Sooh2\\Misc\\Ini
KVObj 依赖 Sooh2\\Misc\\Ini

KVObj 配置文件查找顺序  KVObj.classname => KVObj.default => 默认值 [1,'defaulr']
KVObj 的 dbWithTablename 要在用之前现获取（当链接到同一个服务器时，底层的db是同一个实例，所以后面一个获取到的会覆盖前一个的table）
KVObj 的getCopy（）容易改成 getCopy($uid) {return parent::getCopy(['uid'=>$uid]);},这样改，在继承层级多了以后，容易错，需要进一步改成{
    if($uid===null){return parent::getCopy(null);}
    elseif(is_array($uid)){return parent::getCopy($uid);}
    else {return parent::getCopy(['uid'=>$uid]);}
}

updRecords 的结果
数字是应对知道实际改变记录数量的情况
true是不能获取改变记录数量的情况或改变记录数是0

userId 目前取值10位（考虑到用户个人账户日志，减少索引负载，采用userid10+ymd6+inc3，首位保留，支持近200亿用户，每天100次操作，80年内仍然是有效递增，期间应该需要重构了）
kvobj 可以没有lock字段提高一点性能，但一定要有rowVersion
ini 初始化一定要放在最开始（在其他诸如loger之前）
kvobj的classname默认是处理：转换成全小写，用于找conf和拼接出数据库表名称
kvobj 保存时，键冲突或rowver错误，返回false，其它原因导致的错误抛异常
kvobj 不支持自动递增字段
kvobj 的锁是软锁，没有硬拦截（不写判定代码是可以对锁了的记录进行更新的），逻辑顺序是：load->chklock->[lock and update]->unlock->update
### where 

where的写法参考 [WHERE.md](WHERE.md "where编写说明")

### mysql

1. 获取自增值：->exec(array(['SELECT LAST_INSERT_ID()']));

## 基本使用（等价写法）

### getRecord(tbname, fields, where, sortgrpby)

->getRecord("tb","*,field1",array('&'=>array('k1'=>2,'k2'=>22)), 'sort k1')

数据库 | 效果 
---- |  ---
mysql| select *,field1 from tb where k1=2 and k2=22 order by k1 limit 1;
redis| hGetAll (tb:k1:2:k2:22); (返回结果时会把键值k1,k2拆开放入返回的数组)

### getOne(tbname, fields, where, sortgrpby)

->getRecord("tb","field1",array('&'=>array('k1'=>2,'k2'=>22)), 'sort k1')

数据库 | 效果 
---- |  ---
mysql| select field1 from tb where k1=2 and k2=22 order by k1 limit 1;
redis| hGet (tb:k1:2:k2:22,field1);

