<?php

/**
* 
*/
class EventService extends Services
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
        $this->table = "amely_events";
    }

    public function save($data)
	{
		$relationshipService = RelationshipService::getInstance();
		$userService  = UserService::getInstance();
		$event = new Event();
		foreach ($data as $key => $value) {
			$event->data->$key = $value;
		}
		if ($data['id']) {
			$event->where = "id = {$data['id']}";
			$event_id = $event->update(true);
		} else {
			$event_id = $event->insert(true);
		}
		if ($event_id) {
			InventoryService::getInstance()->save($event_id, "event", $data['creator_id']);
			$owners = $userService->getUsersByType($data['owners_id'], 'id');
			$event = $this->getEventByType($event_id, 'id');
			foreach ($owners as $key => $owner) {
				$relationshipService->save($owner, $event, 'event:invitation');
				$relationshipService->save($event, $owner, 'event:approve');
				$relationshipService->save($event, $owner, 'event:joined');
			}
			return true;
		}
		return false;
	}

	public function published($event_id)
	{
		$userService = UserService::getInstance();
		$event = $this->getEventByType($event_id, 'id');
		if (!$event->published) return false;
		if ($event->invites_id) {
			$invites_id = explode(',', $event->invites_id);
			$invites = $userService->getUsersByType($invites_id, 'id');
			foreach ($invites as $key => $invite) {
				$relationshipService->save($invite, $event, 'event:invitation');
			}
		}
		return true;
	}

	public function getEventsByType($input, $type ='id', $offset = 0, $limit = 10)
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "IN ({$input})",
			'operation' => ''
		];
		$events = $this->getEvents($conditions, $offset, $limit);
		if (!$events) return false;
		return $events;
    }

    public function getEventByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$event = $this->getEvent($conditions);
		if (!$event) return false;
		return $event;
    }

    public function getEvent($conditions)
	{
		$event = $this->searchObject($conditions, 0, 1);
		if (!$event) return false;
		$event = $this->changeStructureInfo($event);
		return $event;
	}

	public function getEvents($conditions, $offset = 0, $limit = 10)
	{
		$events = $this->searchObject($conditions, $offset, $limit);
		if (!$events) return false;
		foreach ($events as $key => $event) {
			$event = $this->changeStructureInfo($event);
			$events[$key] = $event;
		}
		return array_values($events);
	}

	private function changeStructureInfo($event)
	{
		$imageService = ImageService::getInstance();

		$event->avatar = $imageService->showAvatar($event->id, $event->avatar, 'event', 'large');
		$event->cover = $imageService->showCover($event->id, $event->cover, 'event', 'large');

		return $event;
	}
}
