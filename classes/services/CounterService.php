<?php

class CounterService extends Services
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
        $this->table = "amely_counter_offers";
    }

	public function save($data)
	{
		$counter = new Counter();
		$counter->data->owner_id = $data['offer_id'];
	    $counter->data->type = 'offer';
	    $counter->data->title = "";
	    $counter->data->description = "";
	    $counter->data->creator_id = $data['creator_id'];
	    $counter->data->item_id = $data['item_id'];
	    $counter->data->status = $data['status'];

		return $counter->insert(true);
	}    

    public function getCounterByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$counter = $this->getCounter($conditions);
		if (!$counter) return false;
		return $counter;
    }

    public function getCounter($conditions)
	{
		$counter = $this->searchObject($conditions, 0, 1);
		if (!$counter) return false;
		return $counter;
	}

	public function getCounters($conditions, $offset = 0, $limit = 10)
	{
		$counters = $this->searchObject($conditions, $offset, $limit);
		if (!$counters) return false;
		return array_values($counters);
	}
}