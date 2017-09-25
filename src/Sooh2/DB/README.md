# 数据库

## 1.设计目标

1. 提供基本用法，这些基本用法可以忽略掉数据库差异（即同时适用于mysql，mongo，redis等）
2. 提供专属类，可以方便执行数据库专属的一些功能（比如 mysql的 的delay insert，redis的que操作）
3. 针对nosql（Key-Val），提供一套封装，支持分表分库分服务器，同样忽略数据库类型，详情参看 [KVObj](KVObj.md)
4. KVObj既然支持分服务器分库分表，在此基础上加一层，实现简单读写分离（包括cache模式的读写分离）
5. 额外提供一些基于数据库的功能类

- [简单的账户余额流水管理类](Cases/AccountLog.md)
- [交易订单对账系统](Cases/OrdersChk/OrderChk.md)

注意：

作者项目都是以mysql为主，辅以redis、mongo解决一些单一问题，因此redis、mongo接口实现上可能有不足之处，如发现问题，欢迎联系作者。已知问题如下：

1. kvobj 的更新没有使用事务悲观锁机制，靠 update tb set rowVersion=9 where rowVersion=8 来实现的乐观锁
2. redis实现中，主键命名规则是：tablename:pkey1name:pkey1val:pkey2name:pkey2val....，相应的，where只能是pkey（为了支持kvobj,特殊处理支持rowVersion）
3. mongodb 虽然一定程度支持了类sql查询，由于设计复杂性，加上采用mongo是为了nosql,而不是为了sql，因此设计实现上基本用法做了约束：where 是主键（为了支持kvobj,特殊处理支持rowVersion）；当只有一个字段的时候，主键的值直接作为_id，多个字段的时候，pkey1name:pkey1val:pkey2name 作为_id
4. redis，mongo作为nosql，where虽然只有主键，但del和get支持其中一个是数组（比如一组用户id）
5. db是跟connection走的，不是跟db封装类，所以尽量使用 database.table 的格式操作，避免程序转来转去操作错库的情况。

## 2.使用方式

### 2.1 where 的构建方法

        \Sooh2\Messager\Email\SmtpSSL::getInstance('user=yunwei@xyz.com&pass=123456&server=smtp.exmail.qq.com')
        ->sendTo('wangning@zhangyuelicai.com', 'tet测试123423', 'tet测试')

### 2.2 基本用法

        Broker或Broker子类::getInstance()
        ->sendEvtMsg('register', $uid_or_uidArray, ['{replaceFrom}' => $replaceTo]);

### 2.3 专属类

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

数据库里记录了模板id（可以兼事件标识，消息标题模板，消息内容模板，需要往哪些通道发送）

### 2.4 KVObj

设置了数据库链接配置和KVObj配置后

        $obj = /xxx/user::getCopy($uid);
        $obj->load();
        if($obj->exist()){
            echo '用户昵称：'.$obj->getField('nickname');
            try{
                $obj->setField('lastActive',time());
            }catch(\ErrorException $e){
                \Sooh2\Misc\Loger::getInstnace()->app_warning('err:'.$e->getMessage())
            }
        }else{
            echo '用户不存在';
        }

注意： KVObj的更新操作是通过 set rowVersion=10 where xxx and rowVersion=9 这种方式排他锁的，为了实现这个效果，像redis，mongo是做了好几步操作来实现的，
对于高并发特定场景，使用专属代码效率更高

参看: [KVObj详细配置和用法](KVObj.md)

## 3 补充说明

### 3.1 依赖 

- \Sooh2\Misc\Loger 会tracelog所有执行的命令语句
- \Sooh2\Misc\Ini （KVObj使用其获取配置）

### 3.2 重点系统类&常量 

| 类名              | 说明
| ----------------  | ---------------------------------------------------------
| Myisam            | 基于mysqli的，支持myisam和innodb的封装，专属类提供了insert delay 和 reset autoinc
| Redis             | 基于redis的，基础函数封装的是hmap操作，专属类提供了基本键值处理和过期时间设置, 连接配置中user=ignore
| Mongodb           | mongodbx , 连接配置中，user=ignore & pass=ignore 应对无验证情况



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

