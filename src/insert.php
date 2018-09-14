<?php
/**
* 
*/
class SlimInsert extends SlimDatabase
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function saveFeed($feed, $post, $friends = '', $location = '', $access = '', $ossn_photo = '', $mood = '', $share_type = "post", $mobile = false)
	{
		$loggedin_user = loggedin_user();

		$post = $feed->post;
		$friends = $feed->friends;
		$location = $feed->location;

		$post = preg_replace('/\t/', ' ', $post);
		$post = str_replace("\\n\\r", "", $post);
		$wallpost['post'] = htmlspecialchars($post, ENT_QUOTES, 'UTF-8');
		
		//wall tag a friend , GUID issue #566
		if($friends) {
			$friend_guids = explode(',', $friends);
			//reset friends guids
			$friends      = array();
			foreach($friend_guids as $guid) {
				$friends[] = $guid;
			}
			$wallpost['friend'] = implode(',', $friends);
		}
		if($location) {
			$wallpost['location'] = $location;
		}
		//Encode multibyte Unicode characters literally (default is to escape as \uXXXX)
		$feed->description = json_encode($wallpost, JSON_UNESCAPED_UNICODE);


		$object = new stdClass;
		$object->title = $feed->title;
		$object->description = $feed->description;
		$object->subtype = 'wall';
		$object->owner_guid = $feed->owner_guid;
		$object->data->poster_guid = $loggedin_user->guid;
		$object->data->access = $feed->privacy;

		if ($feed->item_type) {
			$object->data->item_type = $feed->item_type;
		}
		if ($feed->share_type) {
			$object->data->share_type = $feed->share_type;
		}
		if ($feed->item_guid) {
			$object->data->item_guid = $feed->item_guid;
		}
		if ($feed->mood) {
			$object->data->mood = $feed->mood;
		}
		if ($feed->linkPreview) {
			$object->data->linkPreview = $feed->linkPreview;
		}
		

	}

}