<?php

/**
* 
*/
class SnapshotService extends Services
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
        $this->table = "amely_snapshots";
    }

    public function save(array $data)
    {
    	
    }

    public function checkExistKey($key)
    {
    	$conditions = null;
    	$conditions[] = [
    		'key' => 'code',
    		'value' => "= '{$key}'",
    		'operation' => ''
    	];
    	$snapshot = $this->getPropertySnapshot($conditions);
    	if (!$snapshot) return false;
    	return $snapshot;
    }

    public function getPropertySnapshot($conditions)
    {
    	$snapshot = $this->searchObject($conditions, 0, 1);
		if (!$snapshot) return false;
		return $snapshot;
    }

    public function getSnapshotByType($input, $type)
	{
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$snapshot = $this->searchObject($conditions, 0, 1);
		if (!$snapshot) return false;
		$snapshot = $this->changeStructureInfo($snapshot);
		return $snapshot;
	}

    public function getSnapshot($conditions)
	{
		$snapshot = $this->searchObject($conditions, 0, 1);
		if (!$snapshot) return false;
		$snapshot = $this->changeStructureInfo($snapshot);
		return $snapshot;
	}

	public function getSnapshots($conditions, $offset = 0, $limit = 10)
	{
		$snapshots = $this->searchObject($conditions, $offset, $limit);
		if (!$snapshots) return false;
		foreach ($snapshots as $key => $snapshot) {
			$snapshot = $this->changeStructureInfo($snapshot);
			$snapshots[$key] = $snapshot;
		}
		return $snapshots;
	}

    public function generateSnapshotKey($obj)
    {
    	$product = clone $obj;
    	$keys_except = ['owner_id','type','time_created','description','tag','number_sold','friendly_url','product_order','downoad','featured','approved','enabled','voucher_category','ticket_category','shop_category','market_categry','category','images','parent_id'];
		foreach ($keys_except as $key) {
			unset($product->$key);
		}
		$keys = (array)$product;
		ksort($keys);
		return md5(serialize($keys));
    }

    public function getPrice($snapshot)
	{
		if ($snapshot->sale_price) {
			return $snapshot->sale_price;
		}
		return $snapshot->price;
	}

    private function changeStructureInfo($snapshot)
	{
		$imageService = ImageService::getInstance();
        $snapshot->description = html_entity_decode($snapshot->description);
        $images = [];
        if ($snapshot->images) {
        	foreach (explode(",", $snapshot->images) as $key => $image) {
        		array_push($images, $imageService->showImage($snapshot->id, $image, 'snapshot', 'large'));
        	}
        } else {
        	array_push($images, $imageService->showImage($snapshot->id, "default", 'snapshot', 'large'));
        }
        if ($images) {
        	$snapshot->images = $images;
        }
        return $snapshot;
    }

}