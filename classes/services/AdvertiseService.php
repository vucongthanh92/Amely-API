<?php

/**
* 
*/
class AdvertiseService extends Services
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
        $this->table = "amely_advertisements";
    }
  

	public function save($data)
	{
		$advertise = new Advertise();
		$advertise->data->owner_id = $data['owner_id'];
		$advertise->data->type = $data['type'];
		$advertise->data->title = $data['title'];
		$advertise->data->description = $data['description'];
		$advertise->data->advertise_type = $data['advertise_type'];
		$advertise->data->time_type = $data['time_type'];
		$advertise->data->target_id = $data['target_id'];
		$advertise->data->image = $data['image'];
		$advertise->data->budget = $data['budget'];
		$advertise->data->cpc = $data['cpc'];
		$advertise->data->link = $data['link'];
		$advertise->data->amount = $data['amount'];
		$advertise->data->start_time = $data['start_time'];
		$advertise->data->end_time = $data['end_time'];
		$advertise->data->enabled = 1
		$advertise->data->total_click = 0;
		$advertise->data->approved = 1;
		$advertise->data->creator_id = $data['creator_id'];
		$advertise_id = $advertise->insert(true);
		return $advertise_id;
	}


    public function getAdvertise($conditions)
	{
		$advertise = $this->searchObject($conditions, 0, 1);
		if (!$advertise) return false;
		return $advertise;
	}

	public function getAdvertises($conditions, $offset = 0, $limit = 10)
	{
		$advertises = $this->searchObject($conditions, $offset, $limit);
		if (!$advertises) return false;
		return array_values($advertises);
	}
}