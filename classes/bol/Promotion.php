<?php
/**
    * time_type
        0 01/01/2011 02:40:23 -> 14/02/2011 22:12:43
        1 02:40:23 -> 22:12:43 && 01/01/2011 -> 14/02/2011

*/
class Promotion extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $time_type;
    private $start_time;
    private $end_time;
    private $status;
    private $approved;

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