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
        
    }

    public function checkExistKey($key, $type = 'product')
    {
    	if ($type == 'product') {
    		$this->table = "amely_product_snapshot";
    	}
    	if ($type == 'detail') {
    		$this->table = "amely_pdetail_snapshot";
    	}
    	$snapshot = $this->getSnapshotByType($key, 'code', false);
    	if (!$snapshot) return false;
    	return $snapshot;
    }

    public function getSnapshotByType($input, $type, $changeStructure = true)
	{
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$snapshot = $this->searchObject($conditions, 0, 1);
		if (!$snapshot) return false;
		if ($changeStructure) {
			$snapshot = $this->changeStructureInfo($snapshot);
		}
		return $snapshot;
	}

	public function getProductsSnapshot($conditions, $offset = 0, $limit = 10, $changeStructure = true)
	{
		$this->table = "amely_product_snapshot";
		$snapshots = $this->getSnapshots($conditions, $offset, $limit, $changeStructure);
		return $snapshots;
	}

	public function getPdetailsSnapshot($conditions, $offset = 0, $limit = 10, $changeStructure = true)
	{
		$this->table = "amely_pdetail_snapshot";
		$snapshots = $this->getSnapshots($conditions, $offset = 0, $limit = 10, $changeStructure = true);
		return $snapshots;
	}

    public function getSnapshot($conditions, $changeStructure = true)
	{
		$snapshot = $this->searchObject($conditions, 0, 1);
		if (!$snapshot) return false;
		if ($changeStructure) {
			$snapshot = $this->changeStructureInfo($snapshot);
		}
		return $snapshot;
	}

	public function getSnapshots($conditions, $offset = 0, $limit = 10, $changeStructure = true)
	{
		$snapshots = $this->searchObject($conditions, $offset, $limit);
		if (!$snapshots) return false;
		if ($changeStructure) {
			foreach ($snapshots as $key => $snapshot) {
				$snapshot = $this->changeStructureInfo($snapshot);
				$snapshots[$key] = $snapshot;
			}
		}
		return $snapshots;
	}

    public function generateSnapshotKey($obj, $type = 'product')
    {
    	if ($type == 'product') {
			$keys = ['owner_id','type','title','description','price','sku','creator_id','sale_price','images'];
    	}
    	if ($type == 'detail') {
			$keys = ['sku','tax','friendly_url','weight','expiry_type','currency','origin','storage_duration','is_special','product_group','creator_id','custom_attributes','duration','begin_day','end_day','manufacturer','unit','adjourn_price','images'];
    	}

    	$arr = [];
		foreach ($keys as $key) {
			if (!array_key_exists($key, (array)$obj)) $obj->$key = "";
			$arr[$key] = $obj->$key;
		}

		return md5(serialize($arr));
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