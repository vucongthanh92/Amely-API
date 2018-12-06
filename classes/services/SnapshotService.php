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
    	$snapshot = new Snapshot();
    	foreach ($data as $key => $value) {
    		$snapshot->data->$key = $value;
    	}
		$snapshot_id = $snapshot->insert(true);
		return $snapshot_id;
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

    public function getSnapshotsByType($input, $type = 'id')
	{
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$snapshots = $this->getSnapshots($conditions, 0, 99999999);
		if (!$snapshots) return false;
		return $snapshots;
	}

    public function getSnapshotByType($input, $type = 'id')
	{
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$snapshot = $this->getSnapshot($conditions);
		if (!$snapshot) return false;
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
        		array_push($images, $imageService->showImage($snapshot->id, $image, 'product', 'large'));
        	}
        } else {
        	array_push($images, $imageService->showImage($snapshot->id, "default", 'product', 'large'));
        }
        if ($images) {
        	$snapshot->images = $images;
        } else {
            unset($snapshot->images);
        }
        $snapshot->display_price = $this->getPrice($snapshot);
        $snapshot->display_currency = "VND";
        if ($snapshot->sale_price) {
        	$snapshot->display_old_price = $snapshot->price;
        }
        return $snapshot;
    }

}