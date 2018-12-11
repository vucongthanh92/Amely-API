<?php

/**
* 
*/
class ProductService extends Services
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
        $this->table = "amely_products";
    }

    public function save($data, $images)
    {
    	$product = new Product();
    	foreach ($data as $key => $value) {
    		$product->data->$key = $value;
    	}
    	$product->data->type = 'shop';
    	$product->data->featured = 0;
		$product->data->product_order = 0;
		$product->data->approved = 0;
        $product->data->enabled = 0;
		$product->data->status = 0;

        if ($data['id']) {
            $product->where = "id = {$data['id']}";
            $product_id = $product->update(true);
        } else {
            $product_id = $product->insert(true);
        }

        if ($product_id) {
            if ($images) {
                $imageService = ImageService::getInstance();
                $filenames = [];
                foreach ($images as $image) {
                    $filename = getFilename();
                    $filenames[] = $filename;
                    $imageService->uploadImage($product_id, 'product', 'images', $image, $filename);
                }
                $product = new Product();
                $product->data->images = implode(',', $filenames);
                $product->data->id = $product_id;
                $product->where = "id = {$product_id}";
                $product->update(true);
            }
            if ($data['images']) {
                $services = Services::getInstance();
                $services->downloadImage($product_id, 'product', 'images', $data['images']);
            }
            return $product_id;
        }
        return false;
    }

    public function excel_product_key()
    {
        $list = [
            'A' => 'is_special',
            'B' => 'title',
            'C' => 'description',
            'D' => 'sku',
            'E' => 'price',
            'F' => 'tax',
            'G' => 'shop_category',
            'H' => 'market_category',
            'I' => 'unit',
            'J' => 'origin',
            'K' => 'manufacturer',
            'L' => 'expiry_type',
            'M' => 'begin_day',
            'N' => 'end_day',
            'O' => 'duration',
            'P' => 'storage_duration',
            'Q' => 'friendly_url',
            'R' => 'product_group',
            'S' => 'weight',
            'T' => 'images'
        ];
        return $list;
    }

    public function product_conditions($product_data)
    {
        if (empty($product_data['title'])) return false;
        switch ($product_data['expiry_type']) {
            case 0:
                break;
            case 1:
                if (empty($product_data['duration'])) 
                    return false;
                break;
            case 2:
                if (!empty($product_data['begin_day']) || !empty($product_data['end_day'])) return false;
                $product_data['begin_day'] = strtotime($product_data['begin_day']);
                $product_data['end_day'] = strtotime($product_data['end_day']);
                break;
            default:
                # code...
                break;
        }
        $shop_categories = $market_categories = $categories = [];
        if ($product_data['market_category']) {
            $market_categories = explode(',', $product_data['market_category']);
            $categories = array_merge($categories, $market_categories);
            $product_data['market_category'] = null;
        }
        if ($product_data['shop_category']) {
            $shop_categories = explode(',', $product_data['shop_category']);
            $categories = array_merge($categories, $shop_categories);
        }
        if ($categories) {
            $categories = array_unique($categories);
            $product_data['category'] = implode(',', $categories);
        }
        if ($product_data['images']) {
            $product_data['images'] = explode(',', $product_data['images']);
        }
        return $product_data;
    }

    public function excel_products($code)
    {
        $path = DIRECTORY_SEPARATOR."import".DIRECTORY_SEPARATOR."{$code}";
        $dir = $settings['image']['path'].$path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file->moveTo($dir . DIRECTORY_SEPARATOR . $filename);
    }

    public function approval($product_id)
    {
    	$snapshotService = SnapshotService::getInstance();
		$product_properties = $this->getPropertyProductByType($product_id);
        if (!$product_properties) return response(false);
        $key = $snapshotService->generateSnapshotKey($product_properties);
        $snapshot = $snapshotService->checkExistKey($key);
        if ($snapshot) {
            $snapshot_id = $snapshot->id;
        } else {
            $snapshot_data = null;
            foreach ($product_properties as $property => $product_property) {
                $snapshot_data[$property] = $product_property;
            }
            unset($snapshot_data['id']);
            $snapshot_data['code'] = $key;
            $snapshot_id = $snapshotService->save($snapshot_data);
        }

        $product = new Product();
        $product->data->approved = time();
        $product->data->snapshot_id = $snapshot_id;
        $product->data->id = $product_id;
        $product->where = "id = {$product_id}";
        return $product->update(true);
    }

    public function updateStatus($product_id, $status)
    {
    	$product = new Product();
    	$product->data->status = $status;
        $product->data->id = $product_id;
    	$product->where = "id = {$product_id}";
    	return $product->update(true);
    }

    public function checkSKUshop($sku, $shop_id)
    {
        $product_params[] = [
            'key' => 'owner_id',
            'value' => "= {$shop_id}",
            'operation' => ''
        ];
        $product_params[] = [
            'key' => 'sku',
            'value' => "= '{$sku}'",
            'operation' => 'AND'
        ];
        $product = $this->getProduct($product_params);
        if (!$product) return false;
        return $product;
    }

    public function checkSKU($sku)
    {
    	$product = $this->getProductByType($sku, 'sku');
    	if (!$product) return false;
    	return $product;
    }

    public function getPropertyProductByType($input, $type = 'id')
    {
        $conditions[] = [
            'key' => $type,
            'value' => "= '{$input}'",
            'operation' => ''
        ];
        $product = $this->searchObject($conditions, 0, 1);
        if (!$product) return false;
        return $product;
    }

    public function getPropertyProduct($conditions)
    {
    	$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		return $product;
    }

    public function getProductByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$product = $this->getProduct($conditions);
		if (!$product) return false;
		return $product;
    }

    public function getProductsByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$products = $this->getProducts($conditions, 0, 999999999);
		if (!$products) return false;
		return $products;
    }

    public function getProduct($conditions)
	{
		$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		$product = $this->changeStructureInfo($product);
		return $product;
	}

	public function getProducts($conditions, $offset = 0, $limit = 10)
	{
		$products = $this->searchObject($conditions, $offset, $limit);
		if (!$products) return false;
		foreach ($products as $key => $product) {
			$product = $this->changeStructureInfo($product);
			$products[$key] = $product;
		}
		return array_values($products);
	}

	public function getPrice($product)
	{
		if ($product->sale_price) {
			return $product->sale_price;
		}
		return $product->price;
	}

	private function changeStructureInfo($product)
	{
		$imageService = ImageService::getInstance();
        $product->description = html_entity_decode($product->description);
        $images = [];
        if ($product->images) {
        	foreach (explode(",", $product->images) as $key => $image) {
        		array_push($images, $imageService->showImage($product->id, $image, 'product', 'large'));
        	}
        } else {
        	array_push($images, $imageService->showImage($product->id, "default", 'product', 'large'));
        }
        if ($images) {
        	$product->images = $images;
        } else {
            unset($product->images);
        }
        $product->display_price = $this->getPrice($product);
        $product->display_currency = "VND";
        if ($product->sale_price) {
        	$product->display_old_price = $product->price;
        }
        return $product;
	}
}