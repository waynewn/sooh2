# 消息发送控制器

## 1.设计目标

1. 对于发送消息，在发送上隔离各个通道的差异（短信、推送、系统内消息、日志等等）
2. 支持预定消息模板，不支持按用户区分替换模板中的内容。需要针对个人发送不同内容的，还是需要一个用户一个用户的发送的。
3. 支持同时向多个地方发送消息，即，根据配置，调用一次，可以同时向几个地方发消息（比如同时发推送和系统消息）
4. 支持通道尝试，比如准备2个短信通道，当一个通道不通的时候，尝试使用另一个

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


### 2.3 如何支持通道尝试

参看 SMS下的auto类的实现，该代码工作需要的配置改动就只有 Messager.Ini

		[smsnotic]
		name = '短信'
		class = "\\Sooh2\\Messager\\SMS\\Auto"
		ini = "senders=sms_aaa,sms_bbb"

		[sms_aaa]
		name = 'aaa短信通道'
		class = "\\xx\\yy\\zz"
		ini = "xxxxx"

		[sms_bbb]
		name = 'bbb短信通道'
		class = "\\xx\\yy\\zz"
		ini = "xxxxx"

## 3 补充说明

### 3.1 依赖

- \Sooh2\Misc\Loger
- 默认的记录发送日志的类是基于\Sooh2\DB\KVObj的 （如果使用了默认的记录发送日志的类）
- \Sooh2\Misc\Ini （如果使用了默认的发送通道识别方法）

### 3.2 重点系统类&常量说明 

| 类名              | 说明
| ----------------  | ---------------------------------------------------------
| MsgTpl类          | 维护数据库中定义的事件消息模板（包括该事件消息需要向哪些渠道发送）
| MsgSentLog类      | 维护消息发送记录，可以做后台重试指定记录的指定通道
| Broker类          | 按事件发送消息的基本类
| BrokerWithLog类   | 在Broker基础上增加记录消息发送记录的能力
| sender及其子类     | 负责具体的发送

- 默认的MsgTpl类，MsgSentLog类是使用的KVObj，需要在KVObj定义的，可以替换成符合接口需求的其它类，然后派生Broker子类
- Sender类及其子类，处理配置时会有服务器地址默认值，改值存在“年久失修”的情况。

### 3.3 重点函数&变量说明

- sendEvtMsg() 根据配置库（或其他）发送 指定 事件消息模板
- sendRetry()  重试发送指定日志的指定通道（这里不支持幂等，使用时注意）
- sendCustomMsg() 直接指定消息发送（不去查配置了，注意，这里还是需要通过用户id获取对应渠道需要的字段的） 

### 3.4 建议通道命名

| 渠道标示          | 说明
| ----------------  | ------------------------------------------------
| msg               | 站内信
| pushstd           | 标准推送（默认消息模式）
| pushext           | 扩展推送（自定义事件模式）
| smscode           | 短信通道（验证码专用通道)
| smsnotic          | 短信通道（通知专用通道）
| smsmarket         | 短信通道（营销专用通道）
| email             | 邮件

### 3.5 默认提供的类需要的相关配置 sql 

        CREATE TABLE `tb_msgtpl_0` (
          `msgid` varchar(36) NOT NULL DEFAULT '' COMMENT '标识',
          `titletpl` varchar(100) NOT NULL DEFAULT '' COMMENT '标题模板',
          `contenttpl` varchar(500) NOT NULL DEFAULT '' COMMENT '内容模板',
          `ways` varchar(64) NOT NULL DEFAULT '' COMMENT '（通道：msg，email...）',
          `rowVersion` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`msgid`)
        )COMMENT '消息模板';


        CREATE TABLE `tb_msgsentlog_0` (
          `logid` bigint(20) NOT NULL,
          `evtid` varchar(32) NOT NULL COMMENT '标识',
          `ymdhis` bigint(20) NOT NULL COMMENT '时间比较另类的记录格式',
          `msgtitle` varchar(200) NOT NULL COMMENT '实际发送的标题',
          `msgcontent` varchar(2000) NOT NULL COMMENT '实际发送的内容',
          `users` varchar(1000) NOT NULL COMMENT '发给哪些用户',
          `ways` varchar(200) NOT NULL  COMMENT '发给哪些通道',
          `sentret` varchar(2000) NOT NULL COMMENT '发送结果',
          `rowVersion` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`logid`)
        )COMMENT '发送记录';