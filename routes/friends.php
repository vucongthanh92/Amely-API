<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->get($container['prefix'].'/friends', function (Request $request, Response $response, array $args) {
	$select = SlimSelect::getInstance();

    $loggedin_user = loggedin_user();
    $params = $request->getQueryParams();

    if (array_key_exists("user_guid", $params) && is_numeric($params['user_guid'])) {
    	$user_params = null;
    	$user_params[] = [
    		'key' => 'guid',
    		'value' => "= {$params['user_guid']}",
    		'operation' => ''
    	];
        $user = $select->getUsers($user_params,0,1);
        if (!$user) return response(false);
    } else {
        $user = $loggedin_user;
    }
    $relation_params = null;
    $relation_params[] = [
    	'key' => 'type',
    	'value' => "= 'friend:request'",
    	'operation' => ''
    ];
    $relation_params[] = [
    	'key' => 'relation_to',
    	'value' => "= {$user->guid}",
    	'operation' => 'AND'
    ];
    $relation_params[] = [
    	'key' => 'relation_from',
    	'value' => '',
    	'operation' => 'query_params'
    ];
    
    $relations = $select->getRelationships($relation_params,0,99999999);
    if ($relations) {
    	$relations_from = array_map(create_function('$o', 'return $o->relation_from;'), $relations);
    	$relations_from = implode(",", $relations_from);

	    $relation_params = null;
	    $relation_params[] = [
	    	'key' => 'type',
	    	'value' => "= 'friend:request'",
	    	'operation' => ''
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_from',
	    	'value' => "= {$user->guid}",
	    	'operation' => 'AND'
	    ];
	    $relation_params[] = [
	    	'key' => 'relation_to',
	    	'value' => "IN ($relations_from)",
	    	'operation' => 'AND'
	    ];
	    
	    $friends = $select->getRelationships($relation_params,0,99999999);
	    $friends_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends);
    	$friends_guid = implode(",", $friends_guid);

    	if ($user->guid != $loggedin_user->guid) {
    		$relation_params = null;
		    $relation_params[] = [
		    	'key' => 'type',
		    	'value' => "= 'friend:request'",
		    	'operation' => ''
		    ];
		    $relation_params[] = [
		    	'key' => 'relation_from',
		    	'value' => "= {$loggedin_user->guid}",
		    	'operation' => 'AND'
		    ];
		    $relation_params[] = [
		    	'key' => 'relation_to',
		    	'value' => "IN ($friends_guid)",
		    	'operation' => 'AND'
		    ];
		    $relation_params[] = [
		    	'key' => 'relation_to',
		    	'value' => '',
		    	'operation' => 'query_params'
		    ];
		    $friends_requested = $select->getRelationships($relation_params,0,99999999);
		    $friends_requested_guid = array_map(create_function('$o', 'return $o->relation_to;'), $friends_requested);
    	}

    	$user_params = null;
    	$user_params[] = [
    		'key' => 'guid',
    		'value' => "IN ({$friends_guid})",
    		'operation' => ''
    	];
    	$friends = $select->getUsers($user_params,0,99999999,true,false);
    	foreach ($friends as $key => $friend) {
    		if ($friend->guid != $loggedin_user->guid) {
		    	$block_list = json_decode($loggedin_user->blockedusers);
			    if (is_array($block_list) && count($block_list) > 0) {
				    if (in_array($friend->guid, $block_list)) {
				    	unset($friends[$key]);
				    }
			    }
			    if (in_array($friend->guid, $friends_requested_guid)) {
	            	$friend->requested = 1;
			    }
    		}
    	}
    	$friends = array_values($friends);
    	return response($friends);
    }

    return response(false);

    // $likes = new OssnLikes;
    // $shops_liked = (array)$likes->GetSubjectsLikes($loggedin_user->guid, "shop");
    // $shops_liked_guid = [];
    // foreach ($shops_liked as $shop) {
    //     $shops_liked_guid[] = $shop->subject_id;
    // }

    

    // if (is_array($friends)) {
    //     foreach ($friends as $key => $friend) {
    //         if (is_numeric($user_guid)) {
    //             if ($friend->guid == $user_guid) {
    //                 unset($friends[$key]);
    //                 continue;
    //             }
    //         } else {
    //             if ($friend->guid == $loggedin_user->guid) {
    //                 unset($friends[$key]);
    //                 continue;
    //             }
    //         }

    //         $block = new OssnBlock;
    //         if (is_numeric($user_guid)) {
    //             if ($block->isBlocked($user_currentview, $friend)) {
    //                 unset($friends[$key]);
    //                 continue;
    //             }
    //         } else {
    //             if ($block->isBlocked($user, $friend)) {
    //                 unset($friends[$key]);
    //                 continue;
    //             }
    //         }
    //         $data = [
    //             'offset' => input('offset', false, '1'),
    //             'limit' => input('limit', false, '1'),
    //             'entities_pairs' => array(
    //                 array(
    //                     "name" => "poster_guid",
    //                     "value" => $group->owner_guid
    //                     )
    //                 )
    //         ];
    //         $friend = ossn_user_remove_attr($friend->guid, true);
    //         if (ossn_relation_exists($loggedin_user->guid, $friend->guid, "friend:request")) {
    //             $friend->requested = 1;
    //         }
    //         $posts = (new OssnWall)->GetPostByOwner($friend->guid, "user", false, $data);
    //         if ($posts) {
    //             $post = $posts[0];
    //             $thought = json_decode(html_entity_decode($post->description));
    //             $friend->thought = $thought->post;
    //             if (!empty($post->mood)) {
    //                 $mood = ossn_get_object($post->mood);
    //                 if ($mood) {
    //                     $mood->icon = $mood->{'file:mood:icon'};
    //                     unset($mood->{'file:mood:icon'});
    //                     $friend->mood = $mood;
    //                 }
    //             }
    //         }
    //         $shop_params = null;
    //         $shop_params[] = [
    //             'key' => 'owner_guid',
    //             'value' => "= {$friend->guid}",
    //             'operation' => ''
    //         ];
    //         $shops = ShopsService::getInstance()->getShopOnView($shop_params);
    //         if ($shops) {
    //             $shop = $shops[0];
    //             if (is_array($shops_liked_guid) && count($shops_liked_guid) > 0) {
    //                 if (in_array($shop->guid, $shops_liked_guid)) {
    //                     $shop->liked = true;
    //                 }
    //             }
                
    //             $friend->shop = $shop;
    //         }
    //         $friends[$key] = $friend;
    //     }
    //     if ($friends) {
    //         return array_values($friends);
    //     }
    //     return false;
    // }
    // return false;

});