<?php
/**
	* subtype
		0 market
		1 voucher
		2 ticket
*/
class Category extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $subtype;
	private $friendly_url;
	private $sort_order;
	private $enabled;
	private $parent_id;
	private $creator_id;
	private $logo;
	
	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_categories";
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