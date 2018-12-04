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
        25 Trao đổi của bạn đã hết hạn.
    order
        11 Đơn hàng #1 chờ xử lý.
        12 Đơn hàng #1 thành công.
        13 Đơn hàng #1 thất bại.
    redeem
        14 #1 sử dụng vật phẩm thành công.
        15 #1 sử dụng vật phẩm thất bại.
    wallet
        16 Nap tien vao vi
        17 Rut tien tu vi
        18 Thanh toan hoa don #1 
        19 Gia han san pham #1
        20 Quang cao san pham #1
        21 Quang cao cua hang #1
        22 Giao hang #1
    shop
        23 Cua hang ABC da bi tu choi
        24 Cua hang ABC da duoc phe duyet


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