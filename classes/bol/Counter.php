<?php
/**
    * status
        0 cho trao doi
        1 thanh cong
        2 huy
*/
class Counter extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $creator_id;
    private $item_id;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_counter_offers";
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