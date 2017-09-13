
## 其它（场景,注意事项）

*记录版本号字段：*

使用时通过自增（update verid加一  where verid=xxx），用于实现行级乐观锁。默认字段名rowVersion，可以通过 define(DBROW_VERFIELD,'重新指定')

*数组数据值*

不同数据库存取类型对数组的处理不太一样

- redis 可以直接存取
- mysql 保存时会转换成json，读出时候不会自动还原

DB 不依赖 Sooh2\Misc\Ini
KVObj 依赖 Sooh2\Misc\Ini

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

