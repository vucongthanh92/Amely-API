<?php

/**
* 
*/
class NotificationService extends Services
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
        $this->table = "amely_notifications";
    }

    public function save(array $data)
    {
    	$notification = Notification();
		$notification->data->owner_id = $data['owner_id'];
		$notification->data->type = $data['type'];
		$notification->data->title = $data['title'];
		$notification->data->description = $data['description'];
		$notification->data->from_id = $data['from_id'];
		$notification->data->from_type = $data['from_type'];
		$notification->data->subject_id = $data['subject_id'];
		$notification->data->item_id = $data['item_id'];
		$notification->data->viewed = 0;
		if ($data['notify_token']) {
			$notification->data->viewed = 1;
			$obj = new stdClass;
			$obj->item_id = $data['item_id'];
			$obj->subject_id = $data['subject_id'];
			$data['data'] = $obj;
			Services::getInstance()->notify($data);
		}
		$notification->data->data = serialize($data['data']);
		return $notification->insert();
    }

    public function viewed($id)
    {
    	if (!$id) return false;
    	$notification = Notification();
    	$notification->data->viewed = 1;
    	$notification->where = "id = {$id}";
    	return $notification->update();
    }

    public function getNotificationsByType($input, $type = 'id', $offset = 0, $limit = 10)
	{
		$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$notifications = $this->searchObject($conditions, $offset, $limit);
		if (!$notifications) return false;
		return array_values($notifications);
	}

    public function getNotification($conditions)
	{
		$notification = $this->searchObject($conditions, 0, 1);
		if (!$notification) return false;
		return $notification;
	}

	public function getNotifications($conditions, $offset = 0, $limit = 10)
	{
		$notifications = $this->searchObject($conditions, $offset, $limit);
		if (!$notifications) return false;
		return array_values($notifications);
	}
}