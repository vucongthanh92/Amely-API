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
		foreach ($data as $key => $value) {
			$advertise->data->$key = $value;
		}
		$advertise->data->enabled = 1;
		$advertise->data->total_click = 0;
		$advertise->data->approved = 0;
		return $advertise->insert(true);
	}

	public function getAdvertiseShop()
	{
		$conditions = $this->getConditionAds(1);
		$advertises = $this->getAdvertises($conditions, 0, 16);
		if (!$advertises) return false;
		return $advertises;
	}

    public function getAdvertiseProduct()
	{
		$conditions = $this->getConditionAds(0);
		$advertises = $this->getAdvertises($conditions, 0, 16);
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

    public function clickAd($advertise_id)
    {
    	$advertise = $this->getAdvertiseByType($advertise_id, 'id');
		if (!$advertise) return false;

		if (((double)$advertise->amount + (double)$advertise->cpc) > $advertise->budget) return false;

		$advertise = object_cast("Advertise", $advertise);
		$advertise->data->total_click = (int) $advertise->total_click + 1;
		$advertise->data->amount = (int) $advertise->amount + (int) $advertise->cpc;
		$advertise->where = "id = {$advertise->id}";
		return $advertise->update();
    }

    public function getAdvertiseByType($input, $type = 'id')
    {
    	$conditions[] = [
    		'key' => $type,
    		'value' => "= '{$input}'",
    		'operation' => ''
    	];

    	$advertise = $this->getAdvertise($conditions);
    	if (!$advertise) return false;
		return $advertise;
    }

    public function getAdvertise($conditions)
	{
		$advertise = $this->searchObject($conditions, 0, 1);
		if (!$advertise) return false;
		$advertise = $this->changeStructureInfo($advertise);
		return $advertise;
	}

	public function getAdvertises($conditions, $offset = 0, $limit = 10)
	{
		$advertises = $this->searchObject($conditions, $offset, $limit);
		if (!$advertises) return false;
		foreach ($advertises as $key => $ad) {
			$ad = $this->changeStructureInfo($ad);
			$advertises[$key] = $ad;
		}
		return array_values($advertises);
	}

	private function changeStructureInfo($ad)
	{
		$imageService = ImageService::getInstance();
		if (isset($ad->image)) {
			if (empty($ad->image)) unset($ad->image);
			$ad->image = $imageService->showImage($ad->id, $ad->image, 'advertise', 'large');
		} else {
			unset($ad->image);
		}

		return $ad;
	}
}