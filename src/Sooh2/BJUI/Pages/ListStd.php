<?php
namespace Sooh2\BJUI\Pages;
/**
 * todo:没处理2层合并式的列表的title
 */
class ListStd extends \Sooh2\HTML\Page
{
    /**
     *
     * @return \Sooh2\BJUI\Pages\ListStd
     */
    public static function getInstance($newInstance = null) {
        return parent::getInstance($newInstance);
    }
    /**
     *
     * @return \Sooh2\BJUI\Pages\ListStd
     */
//    public function initDataUrl()
//    {
//        return $this;
//    }
    /**
     * @param \Sooh2\HTML\Table
     * @return \Sooh2\BJUI\Pages\ListStd
     */
    public function initDatagrid($htmlTableWithoutData)
    {
        $this->_theTable = $htmlTableWithoutData;
        return $this;
    }
    /**
     *
     * @var \Sooh2\HTML\Table
     */
    protected $_theTable;
    protected $_urlForAdd;
    protected $_urlForDL;
    protected $_urlForHelp;
    protected $_urlForMore;
    protected $_iconForMore;
    /**
     *
     * @return \Sooh2\BJUI\Pages\ListStd
     */
    public function initStdBtn($urlForAdd,$urlForDownload=null,$urlForHelp=null,$urlForMore=null,$iconForMore='...')
    {
        $this->_urlForAdd = $urlForAdd;
        $this->_urlForDL = $urlForDownload;
        $this->_urlForMore = $urlForMore;
        $this->_iconForMore = $iconForMore;
        return $this;
    }

    public function render()
    {
        $baseDir = \Sooh2\BJUI\Broker::getInstance()->ini->basedir;
        $s = '';
        //$s .= '<div class="bjui-pageContent"><div class="highlight"><pre class="prettyprint">';

        $s .= '<table class="table table-bordered" data-toggle="datagrid" data-options="{height: \'100%\',showToolbar: false,linenumberAll: true,';
        $s .= "gridTitle : '{$this->title} ";
        if(!empty($this->_urlForAdd)){
            $s.="  <a href=".$this->_urlForAdd." data-toggle=dialog data-options={mask:true,width:800,height:500}><image src={$baseDir}/imgs/btn0_addnew.png border=0/></a>";
        }
        if(!empty($this->_urlForDL)){
            $s.="  <a href=".$this->_urlForDL."  data-options={mask:true,width:800,height:500}><image src={$baseDir}/imgs/btn0_download.png border=0/></a>";
        }
        if(!empty($this->_urlForHelp)){
            $s.="  <a href=".$this->_urlForHelp." data-toggle=dialog data-options={mask:true,width:800,height:500}><image src={$baseDir}/imgs/btn0_help.png border=0/></a>";
        }
        if(!empty($this->_urlForMore)){
            $s.="  <a href=".$this->_urlForMore." data-toggle=dialog data-options={mask:true,width:800,height:500}>{$this->_iconForMore}</a>";
        }
        $s .="',";
        $s .= "toolbarItem: 'all',";
        $s .= "local: 'local',";
        $s .= "dataUrl: '".$this->_theTable->jsonUrl."',";
        //$s .= "editUrl: 'json/ajaxDone.json',";
        $s .= "paging: true";
        $s .='}">';
        $s .= '<thead><tr>';
        foreach($this->_theTable->headers as $h){
            $s.= $this->_fmtHeader($h);
        }
        $s .= '</tr></thead></table>';

        //$s .='</pre></div></div>';
        return $s;
    }
    /**
     *
     * @param \Sooh2\HTML\TableHeader $h
     */
    protected function _fmtHeader($h)
    {
//                <th data-options="{name:'regdate',align:'center',type:'date',pattern:'yyyy-MM-dd HH:mm',render:function(value){return value?value.substr(0,16):value}}">挂号日期</th>
//                <th data-options="{name:'order',align:'center',width:50}">当日序号</th>
//                <th data-options="{name:'regname'}">挂号类别</th>
//                <th data-options="{name:'sex',align:'center',width:45,render:function(value){return String(value)=='true'?'男':'女'}}">性别</th>
//                <th data-options="{name:'age',align:'center',type:'number',width:45,render:function(value){return 2015-parseInt(value)}}">年龄</th>
//                <th data-options="{name:'seedate',align:'center',type:'date',pattern:'yyyy-MM-dd HH:mm:ss'}">就诊时间</th>
        $more = '';
        if($h->css=='type:ymd'){
            $more = ",type:date,pattern:'yyyy-MM-dd HH:mm',render:function(value){return value?value.substr(0,10):value}}";
        }elseif(substr($h->css,0,12)=='type:select-'){
            $tmp = substr($h->css,12);
            if(strpos($tmp, '?')){
                $more = ",type:'select',items:function(){return $.getJSON('".$tmp."&__VIEW__=jsonp&__VIEW___arg=?')},itemattr:{value:'k',label:'v'}";
            }else{
                $more = ",type:'select',items:function(){return $.getJSON('".$tmp."?__VIEW__=jsonp&__VIEW___arg=?')},itemattr:{value:'k',label:'v'}";
            }
        }

        return "<th data-options=\"{name:'{$h->fieldName}',align:'center'".($h->width?",width:{$h->width}":'')." $more}\">{$h->title}</th>";
    }

}