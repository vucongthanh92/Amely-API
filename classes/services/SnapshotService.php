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
    	$snapshot->data->owner_id = $data['owner_id'];
		$snapshot->data->type = $data['type'];
		$snapshot->data->time_created = $data['time_created'];
		$snapshot->data->title = $data['title'];
		$snapshot->data->description = $data['description'];
		$snapshot->data->sku = $data['sku'];
		$snapshot->data->model = $data['model'];
		$snapshot->data->tax = $data['tax'];
		$snapshot->data->weight = $data['weight'];
		$snapshot->data->expiry_type = $data['expiry_type'];
		$snapshot->data->currency = $data['currency'];
		$snapshot->data->origin = $data['origin'];
		$snapshot->data->storage_duration = $data['storage_duration'];
		$snapshot->data->is_special = $data['is_special'];
		$snapshot->data->product_group = $data['product_group'];
		$snapshot->data->creator_id = $data['creator_id'];
		$snapshot->data->custom_attributes = $data['custom_attributes'];
		$snapshot->data->duration = $data['duration'];
		$snapshot->data->begin_day = $data['begin_day'];
		$snapshot->data->end_day = $data['end_day'];
		$snapshot->data->manufacturer = $data['manufacturer'];
		$snapshot->data->price = $data['price'];
		$snapshot->data->sale_price = $data['sale_price'];
		$snapshot->data->unit = $data['unit'];
		$snapshot->data->adjourn_price = $data['adjourn_price'];
		$snapshot->data->code = $data['code'];
		$snapshot->data->images = $data['images'];
		$snapshot->data->parent_id = $data['parent_id'];
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

    public function getSnapshotByType($input, $type = 'id')
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