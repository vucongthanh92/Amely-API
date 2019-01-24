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
  

	public function save($data, $image = false)
	{
		switch ($data['advertise_type']) {
			case 0:
				$advertise_type = 0;
				break;
			case 1:
				$advertise_type = 1;
				break;
			case 2:
				$advertise_type = 2;
				break;
			default:
				return false;
				break;
		}
		$advertise = new Advertise();
		foreach ($data as $key => $value) {
			$advertise->data->$key = $value;
		}
		$advertise->data->advertise_type = $advertise_type;
		$advertise->data->type = 'shop';
		$advertise->data->total_click = 0;
		$advertise->data->approved = 0;
		$advertise->data->status = 0;

		if ($image) {
    		$filename = getFilename();
    		$advertise->data->image = $filename;
    	}

    	if ($data['id']) {
    		$advertise->where = "id = {$data['id']}";
    		$ad_id = $advertise->update(true);
    	} else {
    		$ad_id = $advertise->insert(true);
    	}
    	if ($ad_id) {
			if ($image) {
				$imageService = ImageService::getInstance();			
				$imageService->uploadImage($ad_id, 'advertise', 'images', $image, $filename);
			}
			return $ad_id;
    	}
    	return false;
	}

	public function approval($ad_id)
    {
    	$ad = new Advertise();
    	$ad->data->approved = time();
    	$ad->data->status = 1;
    	$ad->data->id = $ad_id;
    	$ad->where = "id = {$ad_id}";
    	return $ad->update(true);
    }
    
    public function updateStatus($ad_id, $status)
    {
    	$ad = new Advertise();
    	$ad->data->status = $status;
    	$ad->data->id = $ad_id;
    	$ad->where = "id = {$ad_id}";
    	return $ad->update(true);
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

	public function getAdvertiseBanner()
	{
		$conditions = $this->getConditionAds(2);
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
    	$conditions[] = [
			'key' => 'approved',
			'value' => "> 0",
			'operation' => 'AND'
		];
		$conditions[] = [
			'key' => 'status',
			'value' => "= 1",
			'operation' => 'AND'
		];
     	return $conditions;
    }

    public function clickAd($advertise_id)
    {
    	$advertise = $this->getAdvertiseByType($advertise_id, 'id');
		if (!$advertise) return false;

		if (((double)$advertise->cpc) > $advertise->budget) return false;

		$advertise = object_cast("Advertise", $advertise);
		$advertise->data->budget = $advertise->budget - $advertise->cpc;
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