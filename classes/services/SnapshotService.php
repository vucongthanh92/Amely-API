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
    		$this->table = "amely_products_snapshot";
    	}
    	if ($type == 'sub') {
    		$this->table = "amely_sub_products_snapshot";
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

    public function getSnapshot($conditions, $changeStructure = true)
	{
		$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		if ($changeStructure) {
			$product = $this->changeStructureInfo($product);
		}
		return $product;
	}

    public function generateSnapshotKey($obj, $type = 'product')
    {
    	if ($type == 'product') {
			$keys = ['sku','tax','friendly_url','weight','expiry_type','currency','origin','storage_duration','is_special','product_group','creator_id','custom_attributes','duration','begin_day','end_day','manufacturer','unit','adjourn_price','images'];
    	}
    	if ($type == 'sub') {
			$keys = ['owner_id','type','title','description','price','quantity','sku','creator_id','sale_price','images'];
    	}

    	$arr = [];
		foreach ($keys as $key) {
			if (!array_key_exists($key, (array)$obj)) $obj->$key = "";
			$arr[$key] = $obj->$key;
		}

		return md5(serialize($arr));
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
        }
        if ($images) {
        	$snapshot->images = $images;
        }
        return $snapshot;
    }

}