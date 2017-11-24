<?php
namespace Sooh2\DB\Cases;

/**
 * 基于kvobj的cache读写类，主键字段名 cacheKey, 内容字段名  contents
 * 注意：
 *     基于kvobj，所以会尝试json_decode,如果存入的是json_encode后的字符串，取出时会变成数组哈
 *     一般是用redis做cache存储，如果不是，请替换setExpire()
 *     onInit里设置 cacheExpiredIn （默认缓存时间），saveCache()时可以指定本次专用过期秒数
 * 用法：
 *      前面判定是否有cache并获取cache

        $arr4cache = array(
            'location'=>'prdtlist',
            'contractId'=>$contractId,
            'version'=>$version,
            'durationPeriodDays'=>$durationPeriodDays,
            'interestTotal'=>$interestTotal,
            'pageInfo'=>$this->_request->get('pageInfo'),
        );
        $cached = \Sooh2\DB\Cases\CacheSimple::checkExists($arr4cache);
        if($cached){
            return $this->assignRes($cached);
        }
 * 
 *      后面生成数据后，保存cache
 *  \Sooh2\DB\Cases\CacheSimple::saveCache($arr4cache, $list);
 * 
 * @author wangning
 */
class CacheSimple extends \Sooh2\DB\KVObj{
    protected function onInit()
    {
        parent::onInit();
        $this->_tbName = 'tb_cachesimple_{i}';//表名的默认模板
        $this->cacheExpiredIn = 15;
    }
    public static function checkExists($params)
    {
        $k = static::fmtCacheKey($params);
        $obj = static::getCopy(array('cacheKey'=>$k));
        $obj->load();
        if($obj->exists()){
            $tmp = $obj->getField('contents');
            static::freeCopy($obj);
            return $tmp;
        }else{
            return null;
        }
    }

    protected static function fmtCacheKey($params)
    {
        if(is_array($params)){
            $k = md5(json_encode(ksort($params)));
        }else{
            $k = md5($params);
        }
        return $k;
    }
    
    protected function setExpire()
    {
        $db = $this->dbWithTablename();
        $tb = $db->kvobjTable();
        list($dbid,$tbname)= explode('.', $tb);
        $db->useDB($dbid);
        $db->exec(array(['expireAt',$tbname.':cacheKey:'.$this->_pkey['cacheKey'],time()+$this->cacheExpiredIn]));
    }

    public static function saveCache($params,$contents,$secExpire=0)
    {
        $k = static::fmtCacheKey($params);
        $obj = static::getCopy(array('cacheKey'=>$k));
        $obj->load();
        $obj->setField('contents', is_scalar($contents)?$contents: json_encode($contents));
        try{
            $obj->saveToDB();
            if($secExpire!==0){
                $bak = $this->cacheExpiredIn;
                $this->cacheExpiredIn = $secExpire;
                $obj->setExpire();
                $this->cacheExpiredIn=$bak;
            }else{
                $obj->setExpire();
            }
        } catch (\Sooh2\DB\DBErr $ex) {
            \Sooh2\Misc\Loger::getInstance()->app_trace('error on save cache:'.$ex->getMessage());
        }
    }
}
