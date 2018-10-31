<?php

/**
* 
*/
class EventService extends Services
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() 
	{
        $this->table = "amely_events";
    }

    public function save($data)
	{
		
	}

    public function getEventByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$event = $this->getEvent($conditions);
		if (!$event) return false;
		return $event;
    }

    public function getEvent($conditions)
	{
		$event = $this->searchObject($conditions, 0, 1);
		if (!$event) return false;
		return $event;
	}

	public function getEvents($conditions, $offset = 0, $limit = 10)
	{
		$events = $this->searchObject($conditions, $offset, $limit);
		if (!$events) return false;
		return array_values($events);
	}
}
