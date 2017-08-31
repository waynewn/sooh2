<?php
namespace Sooh2\DB;
/**
 * Pager，内部pageid从0开始
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Pager {
	public $page_size=0;//当前使用的分页大小
	public $page_count=0;//当前设置下的页面总数
	public $total;//总记录条数
	public $enumPagesize='';//支持的可选分页尺寸
	private $flgZeroBegin=false;//外部是否0作为第一页，默认false
	protected $page_id_zeroBegin=0;//内部记录的第几页（0开始）
        /**
         * 
         * @param int $pagesize  页面尺寸
         * @param array $pagesizes [可选]支持的分页尺寸列表，透传用的
         * @param bool $zeroBegin [可选] 页码是否从0开始，默认否
         */
        
	public function __construct($pagesize,$pagesizes=array(),$zeroBegin=false) {
		$this->flgZeroBegin=$zeroBegin;
		if(empty($pagesizes)){
			$this->enumPagesize = $this->page_size=$pagesize-0;
		}else{
			if(is_array($pagesizes)){
				$this->enumPagesize=implode(',', $pagesizes);
				if(empty($this->page_size)){
					$this->page_size = $pagesize ? : current($pagesizes);
				}
			}else{
				$this->enumPagesize = $pagesizes;
				if(empty($this->page_size)){
					$pagesizes = explode(',', $pagesizes);
					$this->page_size = $pagesize ? : current($pagesizes);
				}
			}
		}
		if(empty($this->page_size)){
			$this->page_size = 10;
		}
	}
	/**
         * 设置当前页和记录条数，-1 标示本次设置忽略此数值
	 * @param int $total
	 * @param int $pageid
	 * @return \Sooh\DB\Pager
	 */
	public function init($total=-1,$pageid=-1)
	{
		if($pageid===-1){
			$pageid = $this->flgZeroBegin?$this->page_id_zeroBegin:$this->page_id_zeroBegin+1;
		}else{
			$this->page_id_zeroBegin = ($this->flgZeroBegin)?$pageid:$pageid-1;
		}
		if($total!==-1){
			$this->total = $total-0;
			if($this->total==0){
				$this->page_count=1;
			}else{
				$this->page_count = ceil($this->total/$this->page_size);
			}
			if($this->flgZeroBegin){
				if($pageid+1>$this->page_count){
					$this->page_id_zeroBegin=0;
				}else{
					$this->page_id_zeroBegin=$pageid-0;
				}
			}else{
				if($pageid>$this->page_count || empty($pageid)){
					$this->page_id_zeroBegin=0;
				}else{
					$this->page_id_zeroBegin=$pageid-1;
				}
			}
		}
		
		return $this;
	}
	/**
         * 当前页，记录起始位置（0开始的）
         * @return int
         */
        
	public function rsFrom()
	{
		return $this->page_id_zeroBegin*$this->page_size;
	}
	/**
         * 获取当前第几页
         * @return type
         */
	public function pageid()
	{
		if($this->flgZeroBegin){
			return $this->page_id_zeroBegin;
		}else{
			return $this->page_id_zeroBegin+1;
		}
	}
	
	public function toArray()
	{
		return array(
			'pageId'=>$this->pageid(),
			'total'=>$this->total,
			'pageSize'=>  $this->page_size,
			'pageCount'=>$this->page_count
		);
	}
}
