# KVObj

## 1.设计目标

1. 针对nosql（Key-Val），提供一套封装，支持分表分库分服务器，同样忽略数据库类型

注意：

1. 。。。。


## 2.使用方式


### 2.1。。。。







### 2.3 kvobj的释放问题

当任务中大量实例化kvobj的时候（比如后台任务），此时需要手动释放不用了的kvobj资源，方式有2种：

xxxx::freeCopy($obj); //释放指定实例

xxxx::freeCopy();// 释放xxxx的所有实例

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
load
getField  这里注意，默认碰上字段值null，会抛异常
setField
saveToDB
dbAndTbName 返回数据array(DB,fulltablename)
dbWithTablename 返回db,可通过db->kvobjTable()获取tablename，注意，这个函数返回的table只有获取时是可靠的，中间如果其他的kvobj（不同kvobj公用了一个DB）调用过，返回的tablename可能会变化
lock & unlock 增加设置一个用于从记录数据层面锁记录的方式

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

kvobj 可以没有lock字段提高一点性能，但一定要有rowVersion

kvobj的classname默认是处理：转换成全小写，用于找conf和拼接出数据库表名称
kvobj 保存时，键冲突或rowversion错误，返回false，其它原因导致的错误抛异常
kvobj 不支持自动递增字段
kvobj 的锁是软锁，没有硬拦截（不写判定代码是可以对锁了的记录进行更新的），逻辑顺序是：load->chklock->[lock and update]->unlock->update

