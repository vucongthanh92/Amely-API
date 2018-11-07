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
		$advertise->data->enabled = 1;
		$advertise->data->total_click = 0;
		$advertise->data->approved = 1;
		$advertise->data->creator_id = $data['creator_id'];
		$advertise_id = $advertise->insert(true);
		return $advertise_id;
	}

	public function getAdvertiseShop()
	{
		$conditions = $this->getConditionAds(1);
		$advertises = $this->searchObject($conditions, 0, 16);
		if (!$advertises) return false;
		return $advertises;
	}

    public function getAdvertiseProduct()
	{
		$conditions = $this->getConditionAds(0);
		$advertises = $this->searchObject($conditions, 0, 16);
		if (!$advertises) return false;
		return $advertises;
	}

	public function getConditionAds($advertise_type = 0)
    {
    	date_default_timezone_set('Asia/Ho_Chi_Minh');
    	$current_time = time();
    	$time = date("H:i:s", $current_time);

    	$conditions[] = [
	    	'key' => 'cpc',
	    	'value' => "DESC",
	    	'operation' => 'order_by'
	    ];
		$conditions[] = [
    		'key' => 'advertise_type',
    		'value' => "= '{$advertise_type}'",
    		'operation' => ''
    	];
    	$conditions[] = [
    		'key' => '',
    		'value' => "(((DATE_FORMAT(from_unixtime(start_time), '%Y-%m-%d') <= DATE_FORMAT(from_unixtime({$current_time}), '%Y-%m-%d')) AND (DATE_FORMAT(from_unixtime(end_time), '%Y-%m-%d') >= DATE_FORMAT(from_unixtime({$current_time}), '%Y-%m-%d')) AND (DATE_FORMAT(from_unixtime(start_time), '%H:%i:%s') <= DATE_FORMAT(from_unixtime({$current_time}), '%H:%i:%s')) AND (DATE_FORMAT(from_unixtime(end_time), '%H:%i:%s') >= DATE_FORMAT(from_unixtime({$current_time}), '%H:%i:%s')) AND time_type = 1) OR ((start_time < {$current_time}) AND (end_time >= {$current_time}) AND time_type = 0))",
    		'operation' => 'AND'
    	];
    	
    	$conditions[] = [
    		'key' => '(budget*1 - cpc*1)',
    		'value' => ">= (amount*1)",
    		'operation' => 'AND'
    	];
     	return $conditions;
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