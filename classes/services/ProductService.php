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
        $this->table = "amely_products p";
    }

    public function save($data, $images = false)
    {
        $services = Services::getInstance();

    	$product = new Product();
    	foreach ($data as $key => $value) {
    		$product->data->$key = $value;
    	}
    	$product->data->type = 'shop';
    	$product->data->featured = 0;
		$product->data->product_order = 0;
		$product->data->approved = 0;
        $product->data->enabled = 0;
        if ($data['category']) {
            $category = explode(',', $data['category']);
            $category = array_diff($category, ['']);
            $product->data->category = implode(',', $category);
        }
        if ($data['voucher_category']) {
            $voucher_category = explode(',', $data['voucher_category']);
            $voucher_category = array_diff($voucher_category, ['']);
            $product->data->voucher_category = implode(',', $voucher_category);
        }
        if ($data['ticket_category']) {
            $ticket_category = explode(',', $data['ticket_category']);
            $ticket_category = array_diff($ticket_category, ['']);
            $product->data->ticket_category = implode(',', $ticket_category);
        }
        if ($data['shop_category']) {
            $shop_category = explode(',', $data['shop_category']);
            $shop_category = array_diff($shop_category, ['']);
            $product->data->shop_category = implode(',', $shop_category);
        }
        if ($data['market_category']) {
            $market_category = explode(',', $data['market_category']);
            $market_category = array_diff($market_category, ['']);
            $product->data->market_category = implode(',', $market_category);
        }

        if (!$data['friendly_url']) {
        }

        if ($data['id']) {
            $product->where = "id = {$data['id']}";
            $p = $this->getProductByType($data['id'], 'id');
            if ($data['status'] != 1) {
                $services->elasticsearch($p, 'product', 'delete');
            }
            if ($p->approved > 0) {
                if ($data['status'] == 1) {
                    $services->elasticsearch($p, 'product', 'insert');
                }
            }
            $product_id = $product->update(true);
        } else {
            $product_id = $product->insert(true);
        }

        
        if ($product_id) {
            $properties = $this->getPropertyProductByType($product_id, 'id');
            $images_old = explode(',', $properties->images);
            if ($images) {
                $imageService = ImageService::getInstance();
                $filenames = [];
                foreach ($images as $image) {
                    $filename = getFilename();
                    $filenames[] = $filename;
                    $imageService->uploadImage($product_id, 'product', 'images', $image, $filename);
                }
                if (!$images_old) {
                    $images_old = [];
                }
                $images_new = array_merge($images_old, $filenames);
                $images_new = array_diff($images_new, ['']);
                $product = new Product();
                $product->data->images = implode(',', $images_new);
                $product->data->id = $product_id;
                $product->where = "id = {$product_id}";
                $product->update(true);
            }
            if ($data['images']) {
                $services->downloadImage($product_id, 'product', 'images', $data['images']);
            }
            return $product_id;
        }
        return false;
    }


    public function updateMostSold($product_id, $quantity)
    {
        $product = new Product();
        $product->data->id = $product_id;
        $product->data->number_sold = $quantity;
        $product->where = "id = {$product_id}";
        return $product->update(true);
    }

    public function saveByExcel($data)
    {
        $data['description'] = decodeString($data['description']);
        $product = new Product();
        foreach ($data as $key => $value) {
            $product->data->$key = $value;
        }

        $product->data->type = 'shop';
        $product->data->featured = 0;
        $product->data->product_order = 0;
        $product->data->approved = 0;
        $product->data->enabled = 0;
        if ($data['images']) {
            $product->data->images = implode(',', $data['images']);
        }

        if ($data['category']) {
            $category = explode(',', $data['category']);
            $category = array_diff($category, ['']);
            $product->data->category = implode(',', $category);
        }
        if ($data['voucher_category']) {
            $voucher_category = explode(',', $data['voucher_category']);
            $voucher_category = array_diff($voucher_category, ['']);
            $product->data->voucher_category = implode(',', $voucher_category);
        }
        if ($data['ticket_category']) {
            $ticket_category = explode(',', $data['ticket_category']);
            $ticket_category = array_diff($ticket_category, ['']);
            $product->data->ticket_category = implode(',', $ticket_category);
        }
        if ($data['shop_category']) {
            $shop_category = explode(',', $data['shop_category']);
            $shop_category = array_diff($shop_category, ['']);
            $product->data->shop_category = implode(',', $shop_category);
        }
        if ($data['market_category']) {
            $market_category = explode(',', $data['market_category']);
            $market_category = array_diff($market_category, ['']);
            $product->data->market_category = implode(',', $market_category);
        }

        if (!$data['friendly_url']) {
        }

        if ($data['id']) {
            $product->where = "id = {$data['id']}";
            $product_id = $product->update(true);
        } else {
            $product_id = $product->insert(true);
        }

        if ($product_id) {
            // if (is_array($data['images'])) {
            //     $services = Services::getInstance();
            //     $services->downloadImage($product_id, 'product', 'images', $data['images']);
            // }
            return true;
        }
        return false;
    }

    public function queryProductParams($product_params)
    {
        $product_params[] = [
            'key' => 'p.id',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.owner_id',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.type',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.time_created',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.title',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.description',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.sku',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.price',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.snapshot_id',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.model',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.tag',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.number_sold',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.tax',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.friendly_url',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.weight',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.expiry_type',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.currency',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.origin',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.product_order',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.duration',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.storage_duration',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.is_special',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.product_group',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.creator_id',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.custom_attributes',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.download',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.featured',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.begin_day',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.end_day',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.manufacturer',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.sale_price',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.unit',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.approved',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.enabled',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.voucher_category',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.ticket_category',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.shop_category',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.market_category',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.category',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.adjourn_price',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.images',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.parent_id',
            'value' => '',
            'operation' => 'query_params'
        ];
        $product_params[] = [
            'key' => 'p.status',
            'value' => '',
            'operation' => 'query_params'
        ];
        return $product_params;
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
            'T' => 'tag',
            'U' => 'images'
        ];
        return $list;
    }

    public function product_conditions($product_data)
    {
        $categoryService = CategoryService::getInstance();

        if (empty($product_data['title'])) return [
            'status' => false,
            'data' => $product_data
        ];
        switch ($product_data['expiry_type']) {
            case 0:
                break;
            case 1:
                if (empty($product_data['duration'])) 
                    return [
                        'status' => false,
                        'data' => $product_data
                    ];
                break;
            case 2:
                if (empty($product_data['begin_day']) || empty($product_data['end_day'])) return [
                        'status' => false,
                        'data' => $product_data
                    ];
                if (is_numeric($product_data['begin_day']) || is_numeric($product_data['end_day'])) return [
                        'status' => false,
                        'data' => $product_data
                    ];
                $product_data['begin_day'] = $product_data['begin_day']." 00:00:00";
                $product_data['end_day'] = $product_data['end_day']." 23:59:59";
                $product_data['begin_day'] = strtotime($product_data['begin_day']);
                $product_data['end_day'] = strtotime($product_data['end_day']);
                break;
            default:
                # code...
                break;
        }
        if (!$product_data['adjourn_price']) {
            $product_data['adjourn_price'] = 10000;
        }
        $ticket_categories = $voucher_categories = $shop_categories = $market_categories = $categories = [];
        if ($product_data['market_category']) {
            $categories = explode(',', $product_data['market_category']);
            foreach ($categories as $key => $category_id) {
                $category = $categoryService->getCategoryByType($category_id, 'id');
                switch ($category->subtype) {
                    case 0:
                        $market_categories = array_merge($market_categories, [$category->id]);
                        break;
                    case 1:
                        $voucher_categories = array_merge($voucher_categories, [$category->id]);
                        break;
                    case 2:
                        $ticket_categories = array_merge($ticket_categories, [$category->id]);
                        break;
                    case 3:
                        // $shop_categories = array_merge($shop_categories, [$category->id]);
                        break;
                    default:
                        # code...
                        break;
                }
            }
            $product_data['market_category'] = implode(',', $market_categories);
            $product_data['voucher_category'] = implode(',', $voucher_categories);
            $product_data['ticket_category'] = implode(',', $ticket_categories);
        }
        if ($product_data['shop_category']) {
            if (explode(',', $product_data['shop_category'])) {
                foreach (explode(',', $product_data['shop_category']) as $shop_category_id) {
                    $category = $categoryService->getCategoryByType($shop_category_id, 'id');
                    if ($category->subtype == 3) {
                        $shop_categories = array_merge($shop_categories, [$category->id]);
                    }
                }
            }
            $categories = array_merge($categories, $shop_categories);
            $product_data['shop_category'] = implode(',', $shop_categories);
        }
        if ($categories) {
            $categories = array_unique($categories);
            $product_data['category'] = implode(',', $categories);
        }
        if ($product_data['images']) {
            $product_data['images'] = explode(',', $product_data['images']);
        }
        if ($product_data['id']) {
            $product_id = $product_data['id'];
        } else {
            $product_id = false;
        }
        if (!$product_data['friendly_url']) {
            $product_data['friendly_url'] = slugify($product_data['title']);
        }
        // if ($this->checkFriendlyUrl($product_data['id'], $product_data['friendly_url'])) return [
        //     'status' => false,
        //     'data' => $product_data
        // ];
        return [
            'status' => true,
            'data' => $product_data
        ];
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

    public function generateSnapshotSalePrice($product_id, $sale_price)
    {
        global $settings;
        $snapshotService = SnapshotService::getInstance();
        $services = Services::getInstance();

        $product_properties = $this->getPropertyProductByType($product_id);
        if (!$product_properties) return response(false);
        $product_properties->sale_price = $sale_price;
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
        $product->data->sale_price = $sale_price;
        $product->data->snapshot_id = $snapshot_id;
        $product->data->id = $product_id;
        $product->where = "id = {$product_id}";
        if ($product->update(true)) {
            $source = $settings['image']['path']."/product/{$product_id}";
            $dest = $settings['image']['path']."/snapshot/{$snapshot_id}";
            $services->recurse_copy($source, $dest);
            return true;
        }
        return false;
    }

    public function approval($product_id)
    {
        global $settings, $elasticsearch;
    	$snapshotService = SnapshotService::getInstance();
        $services = Services::getInstance();

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
        $p = $this->getProductByType($product_id, 'id');
        
        $product = new Product();
        $product->data->approved = time();
        $product->data->snapshot_id = $snapshot_id;
        $product->data->id = $product_id;
        $product->where = "id = {$product_id}";
        if ($product->update(true)) {
            $services->elasticsearch($p, 'product', 'insert');
            // $params = null;
            // $params = [
            //     'index' => "products",
            //     'type' => "product",
            //     'id' => (int)$p->id,
            //     'body' => [
            //         'Title' => $p->title,
            //         'Phone' => '',
            //         'Username' => '',
            //         'Fullname' => '',
            //         'Email' => '',
            //         'Price' => $p->display_price,
            //         'Image' => $p->images[0],
            //         'Shop'  => ''
            //     ]
            // ];
            // $elasticsearch->index($params);

            $source = $settings['image']['path']."/product/{$product_id}";
            $dest = $settings['image']['path']."/snapshot/{$snapshot_id}";
            $services->recurse_copy($source, $dest);
            return true;
        }
        return false;
    }

    public function updateStatus($product_id, $status)
    {
        $services = Services::getInstance();
        $p = $this->getProductByType($product_id, 'id');

        switch ($status) {
            case 0:
                $services->elasticsearch($p, 'product', 'delete');
                break;
            case 1:
                $services->elasticsearch($p, 'product', 'insert');
                break;
            case 2:
                $services->elasticsearch($p, 'product', 'delete');
                break;
            default:
                # code...
                break;
        }
    	$product = new Product();
    	$product->data->status = $status;
        $product->data->id = $product_id;
    	$product->where = "id = {$product_id}";
    	if ($product->update(true)) {
            
            return true;
        }
        return false;
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

    public function checkFriendlyUrl($product_id = false, $friendly_url)
    {
        $product = $this->getProductByType($friendly_url, 'friendly_url');
        if (!$product) return false;
        if ($product->id == $product_id) return false;
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
        $images_name = [];
        if ($product->images) {
        	foreach (explode(",", $product->images) as $key => $image) {
                $is_http = false;
                $type_list = array("https://", "http://");
                foreach ($type_list as $type) {
                    if (strpos($image, $type) !== false) {
                        $is_http = true;
                    }
                }
                if ($is_http) {
                    array_push($images, $image);
                    array_push($images_name, $image);
                } else {
                    array_push($images_name, $image);
            		array_push($images, $imageService->showImage($product->id, $image, 'product', 'large'));
                }
        	}
        } else {
        	array_push($images, $imageService->showImage($product->id, "default", 'product', 'large'));
        }
        if ($images) {
            $product->images = $images;
        	$product->images_name = $images_name;
        } else {
            unset($product->images);
            unset($product->images_name);
        }
        $product->display_price = $this->getPrice($product);
        $product->display_currency = "VND";
        if ($product->sale_price) {
        	$product->display_old_price = $product->price;
        }
        return $product;
	}
}