<?php
namespace Sooh2\Misc\HttpServer;

/**
 * web server的日志
 * 1）部署时，先跑一次上报,自动建立库表
 * 2）默认是查2分钟前的那一分钟内的记录（需要外部每分钟调用一次）
 * @author simon.wang
 */
class Loger {
    /**
     * 
     * @param \Sooh2\DB\Interfaces\DB $db
     * @param string $tb 表名
     * @return \Sooh2\Misc\HttpServer\Loger
     */
    public static function factory($db,$tb='tb_httpd_error')
    {
        $c = get_called_class();
        return new $c($db,$tb);
    }

    protected function __construct($db,$tb='tb_httpd_error') {
        $this->db=$db;
        $this->tb = $tb;
    }
    protected function splitLine($line)
    {
        $tmp = explode(' ', trim($line));
        $ret = array();
        $buf=null;
        $found = null;
        $match = array('"'=>'"','['=>']','('=>')');
        foreach($tmp as $s){
            if($found!==null){
                if (substr($s, -1)==$found){
                    $ret[] = $buf.' '.substr($s,0,-1);
                    $buf = null;
                    $found=null;
                }else{
                    $buf = $buf.' '.$s;
                }
            }else{
                $c = substr($s,0,1);
                if(isset($match[$c])){
                    $found = $match[$c];
                    if(substr($s, -1)==$found){
                        $ret[] = substr($s, 1,-1);
                        $found = null;
                    }else{
                        $buf = substr($s,1);
                    }
                }else{
                    $ret[] = $s;
                }            
            }
        }
        return $ret;
    }

    protected $tb;
    /**
     *
     * @var \Sooh2\DB\Interfaces\DB 
     */
    protected $db;
    /**
     * 查找上报错误
     */
    public function founderror($serverId,$logfile)
    {
        $return=null;
        //$cmd = "grep 03/May/2017:15:13 $logfile";
        $cmd = "grep ".date("d/M/Y:H:i",time()-120)." $logfile";
        exec($cmd,$return);

        return $this->parseLog($serverId,$return);
    }
    protected function parseLog($serverId,$return)
    {
        $ks = $this->logDefine();

        $ret = array();
        foreach($return as $line){
            $tmp = $this->splitLine($line);

            $r = array_combine($ks, $tmp);

            $err= $this->arrToErr($r, $serverId);
            if(!empty($err)){
                $this->uploadError($err);
            }
        }

        return $ret;
    }
    protected function logDefine()
    {
        return array('remove_addr','http_x_forwarded_for','time','request','code','length','referer','ua','ignore','cookie','skip');
    }
    /**
     * 分析转换日志，比如忽略200的情况，比如忽略已知的攻击日志（或攻击改一下上报情况）
     * @param type $r
     * @param type $serverId
     * @return \Sooh2\Misc\HttpServer\Error
     */
    protected function arrToErr($r,$serverId)
    {
//        if($r['code']==200 || $r['code']==301){
//            return null;
//        }
        $e = new Error;
        $e->agent = $r['ua'];
        $e->cdnip = '';
        $e->code = $r['code'];
        $e->errServer=$serverId;
        $tmp = explode(' ', $r['request']);
        $tmp2 = explode('?',$tmp[1]);
        $e->errUri = array_shift($tmp2);
        $e->fullRequest = $r['request'];
        $e->ip = $r['remove_addr'];
        $e->refer = $r['referer'];
        $e->time = strtotime($r['time']);
        return $e;
    }
    /**
     * 上报日志
     * @param \Sooh2\Misc\HttpServer\Error $err 
     */
    protected function uploadError($err)
    {
        $fields = array(
            'ymdhis'=>date('YmdHis',$err->time),
            'serverIp'=>$err->errServer,
            'uri'=>$err->errUri,
            'refer'=>$err->refer,
            'httpcode'=>$err->code,
            'fullrequest'=>$err->fullRequest,
            'clientIp'=>$err->ip,
	    'cdnIp'=>$err->cdnip,
        );
        if(method_exists($this->db, 'addLog')){
            $this->db->addLog($this->tb, $fields);
        }else{
        $this->db->addRecord($this->tb, $fields);
    }
    }

    /**
     * 默认记录保持30天
     */
    public function removeOldRecord($dayKeep=30)
    {
        $this->db->delRecords($this->tb,array('<ymdhis'=>date("YmdHis",time()-86400*$dayKeep)));
    }
    /**
     * 找到需要发邮件的日志发邮件(一次100个，多出的记录慢慢消化)
     * @param \Sooh2\SMTP $mail 邮件发送类（有方法：setReceiver(一个地址) 和 setMail（title，content）和sendMail（））
     */
    public function reportErrorFound($mail,$arrAddress)
    {
        $this->db->exec(array('create table if not exists '.$this->tb.'('
            . 'autoid bigint not null auto_increment,'
            . 'ymdhis bigint not null default 0,'
            . 'serverIp varchar(16) not null default\'0.0.0.0\','
            . 'uri varchar(200) not null default \'\','
            . 'refer varchar(1000) not null default \'\','
            . 'httpcode int not null default 0,'
            . 'fullrequest varchar(1000) not null default \'\','
            . 'clientIp  varchar(16) not null default\'0.0.0.0\','
            . 'cdnIp  varchar(16) not null default\'0.0.0.0\','
            . 'reported int not null default 0,'
            . 'index reported (reported),'
            . 'primary key (autoid)'
            . ')'));
        $rs = $this->db->getRecords($this->tb, '*',array('reported'=>0),null,100);

        if(empty($rs)){
            return ;
        }
        if(!is_array($arrAddress)){
            $arrAddress = explode(';',$arrAddress);
            if(sizeof($arrAddress)==1){
                $arrAddress = explode(',',$arrAddress);
            }
        }
        foreach($arrAddress as $s){
            $mail->setReceiver($s);
        }
        $mail->setMail('web server error', $this->fmtmail($rs));
        $ret = $mail->sendMail();
        if($ret==false){
            error_log("send mail failed(report httpd error):".$mail->error()."\n");
            echo "send mail failed(report httpd error):".$mail->error()."\n";
        }
        // 顺便删除过于陈旧的记录
    }
    /**
     * 格式化成邮件内容,更新数据库表的对应记录的状态
     */
    protected function fmtmail($rs)
    {
        $arr = array();
        $s = '<table border=1 cellspacing=0 cellpadding=0><tr>';
        $s .= '<th>时间</th><th>错误码</th><th>访问内容</th><th>server</th><th>referred</th>';
        $s .= '</tr>';
        foreach($rs as $r){
            $s .='<tr>';
            $s.= '<td>'.substr($r['ymdhis'],4,2).'-'.substr($r['ymdhis'],6,2).' '.substr($r['ymdhis'],8,2).':'.substr($r['ymdhis'],10,2).'</td>';
            $s.='<td>'.$r['httpcode'].'</td>';
            $s.='<td>'.$r['fullrequest'].'</td>';
            $s.='<td>'.$r['serverIp'].'</td>';
            $s.='<td>'.$r['refer'].'</td>';
            $s .='</tr>';
            $arr[]=$r['autoid'];
        }
        $this->db->updRecords($this->tb, array('reported'=>1),array('autoid'=>$arr));
        return $s.'</table>';
    }


}
