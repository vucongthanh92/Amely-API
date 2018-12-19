<?php

/**
* 
*/
class PermissionService extends Services
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

    public function saveRule($data)
    {
        $rule = new Rule();
        foreach ($data as $key => $value) {
            $rule->data->$key = $value;
        }
        if ($data['id']) {
            $rule->where = "id = {$data['id']}";
            return $rule->update(true);
        } else {
            return $rule->insert(true);
        }
    }

    public function savePermission($data)
    {
        $permission = new Permission();
        foreach ($data as $key => $value) {
            $permission->data->$key = $value;
        }
        if ($data['id']) {
            $permission->where = "id = {$data['id']}";
            return $permission->update(true);
        } else {
            return $permission->insert(true);
        }
    }

    public function saveRulePermission($data)
    {
        $rule_permission = new RulePermission();
        foreach ($data as $key => $value) {
            $rule_permission->data->$key = $value;
        }
        if ($data['id']) {
            $rule_permission->where = "id = {$data['id']}";
            return $rule_permission->update(true);
        } else {
            return $rule_permission->insert(true);
        }
    }

    public function setRuleForUser($user_id, $rule_id)
    {
        $user = new User();
        $user->data->rule = $rule_id;
        $user->where = "id = {$user_id}";
        return $user->update(true);
    }

    public function getRules()
    {
        $this->table = "amely_rule";
        return $this->searchObject(null, 0, 9999999);
    }

    public function getPermission()
    {
        $this->table = "amely_permission";
        return $this->searchObject(null, 0, 9999999);
    }

    public function getPermissionsByRule($rule_id)
    {
        $this->table = "amely_rule_permission";
        $conditions[] = [
            'key' => 'rule_id',
            'value' => "= {$rule_id}",
            'operation' => ''
        ];
        return $this->searchObject($conditions, 0, 9999999);
    }
}