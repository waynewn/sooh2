## WHERE

### 符号含义对应关系

* 『 & 』 AND 
* 『 | 』 OR  
* 『 = 』 =； in (...) ; is null
* 『 ! 』 <> ; not in (...) ; is not null
* 『 > 』 > 
* 『 ] 』 >= 
* 『 < 』 <  
* 『 [ 』 <= 
* 『 * 』 like   

键名以&或|开头，代表and or 转换，此时后面的部分忽略，即 & 等价于 &abc, 这个设定是针对多项的情况，参看后面的例子

### sample

为了便于理解，使用mysql的语法做对照

		array('a'=>1,'b'=>[2,3])              //  where a = 1 and b in (2,3)
		array('|'=>['*a'='sth%', '!b'=>1])       //  where a like 'sth%' or b<>1
		array('a'=>1, '|1'=>['b'=>2,'b'=>3],  '|2'=>['e'=>1,'f'=>1])  // where a=1 and (b=2 or b=3) and (e=>1 or f=1)




