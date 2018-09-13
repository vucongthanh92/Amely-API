<?php
/**
* 
*/
class SlimSelect extends SlimDatabase
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function getAddress($id, $type)
	{
		$table = "ossn_".$type.'s';
		$conditions[] = null;
		$key = $type."id";
		$conditions[] = [
			'key' => $key,
			'value' => "= '{$id}'",
			'operation' => ''
		];
		$addr = $this->getData($table, $conditions, 0, 1, false);
		return $addr[0];
	}

	public function getUsers($conditions, $offset = 0, $limit = 10, $load_more = true, $getAddr = true)
	{
		$table = "users";
		$users = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$users) return false;
		foreach ($users as $key => $user) {
			unset($user->password);
			unset($user->salt);
			$user->fullname = $user->last_name.' '.$user->first_name;
			$avatar = str_replace('profile/photo/', '', $user->avatar);
			$cover = str_replace('profile/cover/', '', $user->cover);

			$avatar_path = "/user/{$user->guid}/profile/photo/"."larger_{$avatar}";
			$cover_path = "/user/{$user->guid}/profile/cover/"."larger_{$cover}";
			if (file_exists(IMAGE_PATH.$avatar_path)) {
				$user->avatar = IMAGE_URL.$avatar_path;
			} else {
				$user->avatar = AVATAR_DEFAULT;
			}
			if (file_exists(IMAGE_PATH.$cover_path)) {
				$user->cover = IMAGE_URL.$cover_path;	
			} else {
				$user->cover = COVER_DEFAULT;
			}
			if ($getAddr) {
				$user_province = $this->getAddress($user->province, 'province');
				$user_district = $this->getAddress($user->district, 'district');
				$user_ward = $this->getAddress($user->ward, 'ward');

			    $user_province = $user_province->type .' '. $user_province->name;
			    $user_district = $user_district->type .' '. $user_district->name;
			    $user_ward = $user_ward->type .' '. $user_ward->name;
			    $user->full_address = $user->address.', '.$user_ward.', '.$user_district.', '.$user_province;
			}

			$users[$key] = $user;
		}
		if ($limit == 1) {
			return $users[0];
		}
		return $users;
	}

	public function getShops($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "shops";
		$shops = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$shops) return false;
		foreach ($shops as $key => $shop) {
			$avatar = array_pop(explode("/", $shop->avatar));
			$cover = array_pop(explode("/", $shop->cover));
			$avatar_path = "/object/{$shop->guid}/avatar/images/"."larger_{$avatar}";
			$cover_path = "/object/{$shop->guid}/cover/images/"."larger_{$avatar}";
			if (file_exists(IMAGE_PATH.$avatar_path)) {
				$shop->avatar = IMAGE_URL.$avatar_path;
			} else {
				$shop->avatar = AVATAR_DEFAULT;
			}
			if (file_exists(IMAGE_PATH.$cover_path)) {
				$shop->cover = IMAGE_URL.$cover_path;	
			} else {
				$shop->cover = COVER_DEFAULT;
			}

		    $shop->description = html_entity_decode($shop->description);
		    $shop->introduce = html_entity_decode($shop->introduce);
		    $shop->policy = html_entity_decode($shop->policy);
		    $shop->contact = html_entity_decode($shop->contact);


		    $owner_province = $this->getAddress($shop->owner_province, 'province');
		    $owner_district = $this->getAddress($shop->owner_district, 'district');
		    $owner_ward = $this->getAddress($shop->owner_ward, 'ward');
		    $owner_province = $owner_province->type .' '. $owner_province->name;
		    $owner_district = $owner_district->type .' '. $owner_district->name;
		    $owner_ward = $owner_ward->type .' '. $owner_ward->name;
		    $shop->owner_full_address = $shop->owner_address.', '.$owner_ward.', '.$owner_district.', '.$owner_province;

		    $shop_province = $this->getAddress($shop->shop_province, 'province');
		    $shop_district = $this->getAddress($shop->shop_district, 'district');
		    $shop_ward = $this->getAddress($shop->shop_ward, 'ward');
		    $shop_province = $shop_province->type .' '. $shop_province->name;
		    $shop_district = $shop_district->type .' '. $shop_district->name;
		    $shop_ward = $shop_ward->type .' '. $shop_ward->name;
		    $shop->full_address = $shop->shop_address.', '.$shop_ward.', '.$shop_district.', '.$shop_province;

		   //  if ($shop->files_scan) {
		   //  	$files_scan = [];
		   //  	$files = explode(";", $shop->files_scan);
		   //  	foreach ($files as $kfile_scan => $vfile_scan) {
		   //  		$photo = str_replace('shop/images/', '', $vfile_scan);
					// $image_file_scan = market_photo_url($shop->guid, $photo, 'shop', 'large');
					// array_push($files_scan, $image_file_scan);
		   //  	}
		   //  	$shop->files_scan = implode(";", $files_scan);
		   //  }

		    $shops[$key] = $shop;
		}
		if ($limit == 1) {
			return $shops[0];
		}
		return $shops;
	}

	public function getStores($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "stores";
		$stores = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$stores) return false;
		foreach ($stores as $key => $store) {

			$store_province = $this->getAddress($shop->owner_province, 'province');
			$store_district = $this->getAddress($shop->shop_district, 'district');
			$store_ward = $this->getAddress($shop->shop_ward, 'ward');

		    $store_province = $store_province->type .' '. $store_province->name;
		    $store_district = $store_district->type .' '. $store_district->name;
		    $store_ward = $store_ward->type .' '. $store_ward->name;
		    $store->full_address = $store->address.' '.$store_ward.' '.$store_district.' '.$store_province;

		    $stores[$key] = $store;
		}
		if ($limit == 1) {
			return $stores[0];
		}
		return $stores;
	}

	public function getRelationships($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_relationships r";
		$ossn_relationships = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $ossn_relationships[0];
		}
		return $ossn_relationships;
	}

	public function getLikes($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_likes";
		$ossn_likes = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $ossn_likes[0];
		}
		return $ossn_likes;
	}

	public function getSiteSettings($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_site_settings";
		$settings = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $settings[0];
		}
		return $settings;
	}

	public function getProductGroup($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "product_group";
		$product_groups = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $product_groups[0];
		}
		return $product_groups;
	}

	public function getGroups($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "groups";
		$groups = $this->getData($table, $conditions, $offset, $limit, $load_more);
		foreach ($groups as $key => $group) {
			$filename = array_pop(explode("/", $group->{"file:avatar"}));
			$file_path = "object/{$group->guid}/avatar/images/larger_{$filename}";
			if (file_exists(IMAGE_PATH.$file_path)) {
				$url = IMAGE_URL.$file_path;
			} else {
				$url = AVATAR_DEFAULT;
			}
			$group->avatar = $url;
			$groups[$key] = $group;
		}
		if ($limit == 1) {
			return $groups[0];
		}
		return $groups;
	}

	public function getAnnotations($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_annotations";
		$annotations = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $annotations[0];
		}
		return $annotations;
	}	

	public function getFeeds($conditions, $offset = 0, $limit = 10, $load_more = true, $owner_guid = null)
	{
		if (!$owner_guid) {
			$owner_guid = loggedin_user()->guid;
		}

		$table = "wallphotos_feeds";
    	$feeds = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$feeds) return false;

		foreach ($feeds as $key => $feed) {
			// $feed = ossn_object_cast('OssnWall', $feed);
			$like_params = null;
			$like_params[] = [
				'key' => 'subject_id',
				'value' => "= {$feed->guid}",
				'operation' => ''
			];
			$like_params[] = [
				'key' => 'type',
				'value' => "= 'post'",
				'operation' => 'AND'
			];
			$likes = $this->getLikes($like_params,0,9999999);
			$feed->liked = false;
			if ($likes) {
				foreach ($likes as $key => $like) {
					if ($like->guid == $owner_guid) {
						$feed->liked = true;
						break;		
					}
				}
			}
			$likes = count($likes);
			if (!$likes) $likes = "0";
			$feed->likes = (string)$likes;
			
			$comment_params = null;
			$comment_params[] = [
				'key' => 'type',
				'value' => "= 'comments:post'",
				'operation' => ''
			];
			$comment_params[] = [
				'key' => 'subject_id',
				'value' => "= {$feed->guid}",
				'operation' => 'AND'
			];
			$comment_params[] = [
				'key' => '',
				'value' => "",
				'operation' => 'count'
			];
			$comments = $this->getAnnotations($comment_params,0,9999);
			if (!$comments) {
				$feed->comments = "0";
			} else {
				$feed->comments = (string) $comments->count;
			}

			if ($feed->images) {
				$photos = explode(",", $feed->images);
				foreach ($photos as $kphoto => $photo) {
					$filename = array_pop(explode("/", $photo));
					$file_path = "/object/{$feed->guid}/ossnwall/images/"."lgthumb_{$filename}";
					if (file_exists(IMAGE_PATH.$file_path)) {
						$url = IMAGE_URL.$file_path;
					} else {
						$url = AVATAR_DEFAULT;
					}
					$photos[$kphoto] = $url;
				}
				$feed->wallphoto = $photos;
			}
			// $photos = $od->select($photo_params, true);
			$data = json_decode(html_entity_decode($feed->description));
			if (property_exists($data, 'post')) $desc['post'] = $data->post;
			if (property_exists($data, 'location')) $desc['location'] = $data->location;
			if (property_exists($data, 'friend')) $desc['friend'] = $data->friend;
			$feed->desc = $desc;
			$feeds[$key] = $feed;
		}
		if ($limit == 1) {
			$feeds = $feeds[0];
		}
		return $feeds;
	}
	
	public function getObjects($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_object";
		$objects = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $objects[0];
		}
		return $objects;
	}

	public function getLinkPreview($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "feed_linkpreview";
		$links = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $links[0];
		}
		return $links;
	}

	public function getMoods($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "moods";
		$moods = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $moods[0];
		}
		return $moods;
	}

	public function getTokens($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "ossn_usertokens";
		$tokens = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $tokens[0];
		}
		return $tokens;
	}

	public function getAdvertisements($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "advertisements";
		$advertisements = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if ($limit == 1) {
			return $advertisements[0];
		}
		return $advertisements;
	}

	public function getCategories($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "categories";
		$categories = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$categories) return false;
		foreach ($categories as $key => $category) {
			if ($category->subtype == "shop:category" || $category->subtype == "market:category") {
				$filename = array_pop(explode("/", $category->logo));
				$file_path = "/object/{$category->guid}/category/images/"."lgthumb_{$filename}";
				if (file_exists(IMAGE_PATH.$file_path)) {
					$url = IMAGE_URL.$file_path;
				} else {
					$url = AVATAR_DEFAULT;
				}

				$category->logo = $url;
				$categories[$key] = $category;
			} 
		}
		if ($limit == 1) {
			return $categories[0];
		}
		return $categories;
	}

	public function getProducts($conditions, $offset = 0, $limit = 10, $load_more = true, $currency_code = "VND", $encode = true)
	{
		$table = "categories_images_products";
		$products = $this->getData($table, $conditions, $offset, $limit, $load_more);
        if (!$products) return false;

        foreach ($products as $key => $product) {
            if (!$currency_code) $currency_code = $product->currency;
            if ($encode) {
                $product->description = html_entity_decode($product->description);
            }
            $product->shop_categories = explode(",", $product->shop_category);
            $product->market_categories = explode(",", $product->market_category);
            $product->voucher_categories = explode(",", $product->voucher_category);

            $images = [];
            $images_entities = [];
            if ($product->images) $images = explode(",", $product->images);
            if ($product->images_entities) $images_entities = explode(",", $product->images_entities);

            $entities = [];
            $entities_guid = [];

            if ($images) {
                foreach ($images as $kimage => $image) {
                    $is_http = false;
                    $type_list = array("https://", "http://");
                    foreach ($type_list as $type) {
                        if (strpos($image, $type) !== false) {
                            $is_http = true;
                        }
                    }
                    if ($is_http) {
                        $images[$kimage] = $image;
                        $entities[$images_entities[$kimage]] = $images[$kimage];
                        array_push($entities_guid, $images_entities[$kimage]);
                    } else {
                    	$filename = $image;
						$file_path = "/object/{$product->guid}/product/images/"."lgthumb_{$filename}";
						if (file_exists(IMAGE_PATH.$file_path)) {
							$url = IMAGE_URL.$file_path;
						} else {
							$url = AVATAR_DEFAULT;
						}

                        $images[$kimage] = $url;
                        $entities[$images_entities[$kimage]] = $images[$kimage];
                        array_push($entities_guid, $images_entities[$kimage]);
                    }
                }
            }
            $product->images = $images;
            $product->images_entities = $entities;

            $product->images = $images;
            $product->category = explode(",", $product->category);

            $display_price = getPrice($product);
            $product->display_price = $display_price;
            $product->display_currency = $currency_code;
            $product->display_old_price = $product->price;

            $products[$key] = $product;
        }
		if ($limit == 1) {
			return $products[0];
		}
		return $products;
	}

	public function getOffers($conditions, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "offers";
		$offers = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$offers) return false;
		foreach ($offers as $key => $offer) {
			if ($offer->offer_type == "random") {
				$offer->counter_number = $offer->counter_number - 1;
			}
			$offers[$key] = $offer;
		}
		if ($limit == 1) {
			return $offers[0];
		}
		return array_values($offers);
	}

	public function getSnapshots($conditions = null, $offset = 0, $limit = 10, $load_more = true, $currency_code = "VND")
	{
		$table = "products_snapshot";
        $snapshots = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$snapshots) return false;

		foreach ($snapshots as $key => $snapshot) {
			if (!$currency_code) $currency_code = $snapshot->currency;
			$snapshot->description = html_entity_decode($snapshot->description);
			$snapshot->shop_categories = explode(",", $snapshot->shop_category);
			$snapshot->market_categories = explode(",", $snapshot->market_category);
			$snapshot->voucher_categories = explode(",", $snapshot->voucher_category);
			$images = explode(",", $snapshot->images);
			if ($images) {
				foreach ($images as $kimage => $image) {
					$is_http = false;
                    $type_list = array("https://", "http://");
                    foreach ($type_list as $type) {
                        if (strpos($image, $type) !== false) {
                            $is_http = true;
                        }
                    }
                    if ($is_http) {
                        $images[$kimage] = $image;
                    } else {
                    	$filename = $image;
						$file_path = "/object/{$snapshot->guid}/product/images/"."lgthumb_{$filename}";
						if (file_exists(IMAGE_PATH.$file_path)) {
							$url = IMAGE_URL.$file_path;
						} else {
							$url = AVATAR_DEFAULT;
						}
						$images[$kimage] = $url;
                    }
						
				}
			}
			$snapshot->images = $images;
			$snapshot->category = explode(",", $snapshot->category);

			$display_price = getPrice($snapshot);
			$snapshot->display_price = $display_price;
			$snapshot->display_currency = $currency_code;
			$snapshot->display_old_price = $snapshot->price;

			$snapshots[$key] = $snapshot;
		}

		if ($limit == 1) {
			$snapshots = $snapshots[0];
		}

		return $snapshots;
	}

	public function getCounters($conditions = null, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "counter_offers";
        $counters = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$counters) return false;
		if ($limit == 1) {
			$counters = $counters[0];
		}
		return $counters;
	}

	public function getItems($conditions = null, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "items";
        $items = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$items) return false;
		if ($limit == 1) {
			$items = $items[0];
		}
		return $items;
	}

	public function getItemsInventory($conditions = null, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "items_inventory";
        $items = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$items) return false;
		if ($limit == 1) {
			$items = $items[0];
		}
		return $items;
	}

	public function getProductsMarket($conditions, $offset = 0, $limit = 10, $load_more = true, $currency_code = "VND", $encode = true)
	{
		$table = "products_market";
        $products = $this->getData($table, $conditions, $offset, $limit, $load_more);
        if (!$products) return false;

        foreach ($products as $key => $product) {
            if (!$currency_code) $currency_code = $product->currency;
            if ($encode) {
                $product->description = html_entity_decode($product->description);
            }
            $images = [];
            if ($product->images) $images = explode(",", $product->images);

            if ($images) {
                foreach ($images as $kimage => $image) {
                    $is_http = false;
                    $type_list = array("https://", "http://");
                    foreach ($type_list as $type) {
                        if (strpos($image, $type) !== false) {
                            $is_http = true;
                        }
                    }
                    if ($is_http) {
                        $images[$kimage] = $image;
                    } else {
                    	$filename = $image;
						$file_path = "/object/{$product->guid}/product/images/"."lgthumb_{$filename}";
						if (file_exists(IMAGE_PATH.$file_path)) {
							$url = IMAGE_URL.$file_path;
						} else {
							$url = AVATAR_DEFAULT;
						}
                        $images[$kimage] = $url;
                    }
                }
            }
            $product->images = $images;
            $product->category = explode(",", $product->category);

            $display_price = getPrice($product);
            $product->display_price = $display_price;
            $product->display_currency = $currency_code;
            $product->display_old_price = $product->price;

            $products[$key] = $product;
        }
		if ($limit == 1) {
			return $products[0];
		}
		return $products;
	}
	
	public function getEvents($conditions = null, $offset = 0, $limit = 10, $load_more = true)
	{
		$table = "events";
        $events = $this->getData($table, $conditions, $offset, $limit, $load_more);
		if (!$events) return false;
		foreach ($events as $key => $event) {
			$filename = array_pop(explode("/", $event->{"file:avatar"}));
			$file_path = "/object/{$event->guid}/avatar/images/"."larger_{$filename}";
			if (file_exists(IMAGE_PATH.$file_path)) {
				$url = IMAGE_URL.$file_path;
			} else {
				$url = AVATAR_DEFAULT;
			}
			$event->avatar = $url;
			$events[$key] = $event;
		}
		$events = array_values($events);
		if ($limit == 1) {
			$events = $events[0];
		}
		return $events;
	}
	
}
