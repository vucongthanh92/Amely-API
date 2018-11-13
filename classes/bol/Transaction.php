<?php
/**
    gift
        0 Ban da tang qua #1
        1 Ban da dong y nhan qua #1
        2 Ban da tu choi qua #1
    offer
        3 Bạn đã tạo trao đổi #1
        4 Bạn đã xóa trao đổi #1
        5 Bạn đã đồng ý trao đổi #1
        6 Bạn đã từ chối trao đổi #1 
        7 Bạn đã tham gia trao đổi #1 
        8 Bạn đã hủy yêu cầu trao đổi #1
        9 Đề xuất trao đổi # bị từ chối
        10 Đề xuất trao đổi # thành công.


*/
class Transaction extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $subject_type;
    private $subject_id;
    private $status;
    
	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_transactions";
	}

	public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
        	$this->$key = $value;
        }
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
    }
}