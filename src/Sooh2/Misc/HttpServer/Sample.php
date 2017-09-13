<?php
//定义一个处理类，里面略掉定制性的内容，比如，只记录失败的，报错的，忽略掉扫描的
        $server=$this->_request->get('server',null);
        $dbIni = \Sooh2\Misc\Ini::getInstance()->getIni('DB.dblog');
        $sys = \Rpt\Misc\NginxLog::factory(\Sooh2\DB::getConnection($dbIni), 'db_log.tb_httpd_log');
        if($server){//每分钟启动检查一下，上报汇总
            $sys->founderror($server, '/usr/local/openresty/nginx/logs/access.log');
        }else{//发邮件
            $mailSys = new \Prj\Tool\Email();
            $sys->reportErrorFound($mailSys->getMail(), $mailSys->addrForHttpdError());
        }

class SampleLogProcess extends \Sooh2\Misc\HttpServer\Loger{
    protected function logDefine()
    {
        return array('remove_addr','http_x_forwarded_for','time','request','code','length','referer','ua','ignore','cookie','skip');
    }
    protected function arrToErr($r,$serverId)
    {
        $e = parent::arrToErr($r, $serverId);
        if(empty($e)){
            return $e;
        }
        if($e->code==200 ||$e->code==206 || $e->code==301 || $e->code==304){
            return null;
        }
        if($this->inIgnoreList($e->errUri)){
            return null;
        }
        $e->time = strtotime($r['time']);
        $e->cdnip = $r['remove_addr'];
        $e->ip = array_shift(explode(',', $r['http_x_forwarded_for']));
        return $e;
    }
    protected function inIgnoreList($uri){
        if(substr($uri,0,2)=='\x'){
            return true;
        }
        return in_array($uri, array(
            '/apple-touch-icon.png',
            '/information/xwdt/62.html',
            '/information/xwdt/76.html',
            '/information/xwdt/84.html',
            '/information/hlwjr/747.html',
            '/information/lczs/408.html',
            '/information/lczs/1204.html',
            '/information/lczs/1255.html',
            '/information/lczs/1610.html',
        ));
    }
}