<?php

class Events extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $subtype;
	private $start_date;
	private $end_date;
	private $country;
	private $location;
	private $template;
	private $has_inventory;
	private $status;
	private $creator_id;
	private $members;
	private $invites;
	private $friendly_url;
	private $published;
	private $avatar;
	private $cover;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_events";
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