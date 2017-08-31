## WHERE

### 本文介绍where的基本写法，为了便于理解，使用mysql的where做对照


* 『 & 』 AND 
* 『 | 』 OR  
* 『 = 』 =； in (...) ; is null
* 『 ! 』 <> ; not in (...) ; is not null
* 『 > 』 > 
* 『 ] 』 >= 
* 『 < 』 <  
* 『 [ 』 <= 
* 『 * 』 like   

### sample


		array(a=>1,b=>[2,3])              //  where a = 1 and b in (2,3)
		array(|=>[*a='sth%',!b=>1])       //  where a like 'sth%' or b<>1
		array(a=>1, !1=>[b=>2,b=>3],|3=>[e=>1,f=>1])  // where a=1 and (b=2 or b=3) and (e=>1 or f=1)




