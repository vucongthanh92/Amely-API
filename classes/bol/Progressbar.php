<?php
/*
A is_special
B title
C description
D sku
E price
F tax
G shop_category
H market_category
I unit
J origin
K manufacturer
L expiry_type
M begin_day
N end_day
O duration
P storage_duration
Q friendly_url
R product_group
S weight
T images
*/

class Progressbar extends Object
{
    private $owner_id;
    private $type;
    private $code;
    private $time_created;
    private $inserted;
    private $updated;
    private $error;
    private $filename;
    private $number;
    private $total_number;
    private $creator_id;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_progressbar";
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