# 数据库

## 1.设计目标

1. 提供基本用法，这些基本用法可以忽略掉数据库差异（即同时适用于mysql，mongo，redis等），exec方法虽然作为基本用法类提供的方法，其实是具体类型数据库支持的语法
2. 提供专属类，可以更方便的执行数据库专属的一些功能（比如 mysql的 的delay insert，redis的que操作）
3. 针对nosql（Key-Val），提供一套封装，支持分表分库分服务器，同样忽略数据库类型，详情参看 [KVObj](KVObj.md)
4. KVObj既然支持分服务器分库分表，在此基础上加一层，实现简单读写分离（包括cache模式的读写分离）
5. 额外提供一些基于数据库的功能类

- [简单的账户余额流水管理类](Cases/AccountLog.md)
- [交易订单对账系统](Cases/OrdersChk/OrderChk.md)

注意：

作者项目都是以mysql为主，辅以redis、mongo解决一些单一问题，因此redis、mongo接口实现上可能有不足之处，如发现问题，欢迎联系作者。已知问题如下：

1. kvobj 的更新没有使用事务悲观锁机制，靠 update tb set rowVersion=9 where rowVersion=8 来实现的乐观锁
2. redis实现中，主键命名规则是：tablename:pkey1name:pkey1val:pkey2name:pkey2val....，相应的，where只能是pkey（为了支持kvobj,特殊处理支持rowVersion）
3. mongodb 虽然一定程度支持了类sql查询，但性能有问题，所以一般只是作为文档数据库，因此设计实现上基本用法做了约束：where 是主键（为了支持kvobj,特殊处理支持rowVersion）；当只有一个字段的时候，主键的值直接作为_id，多个字段的时候，pkey1name:pkey1val:pkey2name 作为_id
4. redis，mongo作为nosql，where虽然只有主键，但del和get支持其中一个是数组（比如一组用户id）
5. db是跟connection走的，不是跟db封装类，所以尽量使用 database.table 的格式操作，避免程序跳来跳去，操作错库的情况。
6. 作者经历的项目，都是以mysql为核心，redis为缓存，mongodb为文档服务器的，所以专属类实现的并不多，你可以根据自己的情况扩展。

## 2.使用方式

### 2.1 where 的构建方法

以mysql的语法为例：

		array('a'=>1,'b'=>[2,3])              //  where a = 1 and b in (2,3)
		array('|'=>['*a'='sth%', '!b'=>1])       //  where a like 'sth%' or b<>1
		array('a'=>1, '|1'=>['b'=>2,'b'=>3],  '|2'=>['e'=>1,'f'=>1])  // where a=1 and (b=2 or b=3) and (e=>1 or f=1)

更多详细用法参见： [Where用法](WHERE.md)

### 2.2 基本用法

        $db = \Sooh2\DB::getDB($ini);//获取数据库实例(在没有实际操作之前，不连接数据库)
        $db->addRecord('db.table1',array('createTime'=>'2017-1-1'),array('id'=>1,));//为了兼容

        $r = $db->getRecord('db.table1','*', array('a'=>$db->getCol('db.table2','id',array('>createTime'=>'2017-1-1'))));
        // select * from db.table1 where a in (select id from db.table2 where createTime>'2017-1-1');

        $rs = $db->exec(array("select * from db.tb"));//虽然是基本用法类提供的方法，其实是具体类型数据库支持的语法，使用时要注意
        $rs = $db->fetchResultAndFree($rs);
        
        echo $db->lastCmd(); 

        //框架级程序执行完后，建议执行 \Sooh2\DB::free() 释放资源
### 2.3 专属类

数据库特性操作，比如redis设置超时等放在专属类里

        \Sooh2\DB\Redis\Special::getInstance(array(配置....))
        或  \Sooh2\DB\Redis\Special::getInstance($db->getConn())// 这种要求你能确认此时的db是对应的数据库类型，本例中是redis

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
对于特定场景，使用专属类完成一些操作更合理，效率也更高

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
addRecord   失败要么异常，要么返回false；成功：如果支持自增字段，返回自增结果，否则返回true；第三个参数，主键数组，mysql不用拆，但是为了兼容nosql，所以拆开了
updRecords  失败要么异常，要么返回false；成功：如果支持变动记录数，返回变更记录数量，否则返回true
delRecord   失败要么异常，要么返回false；成功：如果支持变动记录数，返回变更记录数量，否则返回true
getOne      失败要么异常，要么返回null；成功：返回指定记录的那个字段的值
getPair     失败要么异常，要么返回array()；成功：将结果以key=>val 的数据形式返回
getRecord   失败要么异常，要么返回array()；成功：返回指定记录
getRecords  失败要么异常，要么返回array()；成功：返回指定记录集
getRecordCount  失败异常，成功：返回指定记录条数
exec & fetchResultAndFree 虽然是基本用法类提供的方法，其实是具体类型数据库支持的语法，使用时要注意
lastCmd     返回字符串：最后执行的命令字符串（mysql来讲就是sql）

### 3.4 默认提供的类需要的相关配置 sql

    无

## 其它（场景,注意事项）

*记录版本号字段：*

使用时通过自增（update verid加一  where verid=xxx），用于实现行级乐观锁。默认字段名rowVersion，可以通过 define(DBROW_VERFIELD,'重新指定字段名')

