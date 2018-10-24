<?php
/**
    * advertise_type
        0 product
        1 shop
        2 banner

    * time_type
        0 01/01/2011 02:40:23 -> 14/02/2011 22:12:43
        1 02:40:23 -> 22:12:43 && 01/01/2011 -> 14/02/2011

*/
class Advertise extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $advertise_type;
    private $time_type;
    private $target_id;
    private $image;
    private $budget;
    private $cpc;
    private $link;
    private $amount;
    private $start_time;
    private $end_time;
    private $enabled;
    private $total_click;
    private $approved;
    private $creator_id;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_advertisements";
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