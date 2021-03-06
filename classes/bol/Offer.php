<?php
/**
    * status
        0 cho trao doi
        1 thanh cong
        2 huy

    * offer_type
        0 normal
        1 random
        2 giveaway

    * target
        0 public
        1 friends
        2 location
*/
class Offer extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $target;
    private $duration;
    private $offer_type;
    private $expried;
    private $status;
    private $option;
    private $limit_counter;
    private $item_id;
    private $note;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_offers";
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