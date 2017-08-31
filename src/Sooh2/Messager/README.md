# 事件消息

- 支持按事件找出预定的需要向哪些渠道发送消息（遵循规范可以自行修改和增减）
- 支持按事件找出预定的消息模板
- 一般一组用户不要超过100个，请自行拆发，目前类库内部没有相关处理

## 使用方法

1. 需要实现UserInfo接口的类，用于获取用户的手机号，推送用id，站内信用id，email用于发送
2. 需要一个地方配置各个发送渠道实际对应的类和配置，配置文字串是http query的格式
3. 根据需要派生Broker或BrokerWithLog的子类，里面确认相应的消息配置、渠道配置、日志、用户信息获取等操作，然后调用相应的消息发送方法 

- sendEvtMsg 根据配置库（或其他）发送 指定 事件消息模板
- sendRetry  重试发送指定日志的指定通道（这里不支持幂等，使用时注意）
- sendCustomMsg 直接指定消息发送（不去查配置了，注意，这里还是需要通过用户id获取对应渠道需要的字段的） 

## 建议渠道命名

| 渠道标示          | 说明
| ----------------  | ------------------------------------------------
| msg               | 站内信
| pushstd           | 标准推送（默认消息模式）
| pushext           | 扩展推送（自定义事件模式）
| smscode           | 短信通道（验证码专用通道)
| smsnotic          | 短信通道（通知专用通道）
| smsmarket         | 短信通道（营销专用通道）
| email             | 邮件

## 其它类说明

| 类名              | 说明
| ----------------  | ---------------------------------------------------------
| MsgTpl类          | 维护数据库中定义的事件消息模板（包括该事件消息需要向哪些渠道发送）
| MsgSentLog类      | 维护消息发送记录，可以做后台重试指定记录的指定通道
| Broker类          | 按事件发送消息的基本类
| BrokerWithLog类   | 在Broker基础上增加记录消息发送记录的能力

- 默认的MsgTpl类，MsgSentLog类是使用的KVObj，需要在KVObj定义的，可以替换成符合接口需求的其它类，然后派生Broker子类


## 默认提供的类需要的相关配置 sql

### MsgTpl 类

需要 KVObj 配置 MsgTpl

        CREATE TABLE `tb_msgtpl_0` (
          `msgid` varchar(36) NOT NULL DEFAULT '',
          `titletpl` varchar(100) NOT NULL DEFAULT '',
          `contenttpl` varchar(500) NOT NULL DEFAULT '',
          `ways` varchar(64) NOT NULL DEFAULT '' COMMENT '（msg：站内信等）',
          `rowVersion` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`msgid`)
        );

### MsgSentLog 类

需要 KVObj 配置 MsgSentLog

        CREATE TABLE `tb_msgsentlog_0` (
          `logid` bigint(20) NOT NULL,
          `evtid` varchar(32) NOT NULL,
          `ymdhis` bigint(20) NOT NULL,
          `msgtitle` varchar(200) NOT NULL,
          `msgcontent` varchar(2000) NOT NULL,
          `users` varchar(1000) NOT NULL,
          `ways` varchar(200) NOT NULL,
          `sentret` varchar(2000) NOT NULL,
          `rowVersion` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`logid`)
        );