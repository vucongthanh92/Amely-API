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
        $rule_permission->data->type = 'rule';
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
        $user->data->id = $user_id;
        $user->data->rule = $rule_id;
        $user->where = "id = {$user_id}";
        return $user->update(true);
    }

    public function checkPermission($rule_id, $path, $method)
    {   
        $type = "";
        switch ($method) {
            case 'GET':
                $type = 'get';
                break;
            case 'POST':
                $type = 'post';
                break;
            case 'PUT':
                $type = 'put';
                break;
            case 'PATCH':
                $type = 'patch';
                break;
            case 'DELETE':
                $type = 'delete';
                break;
            default:
                return false;
                break;
        }
        
        $permission = $this->getPermissionByPath($path);
        if (!$permission) return false;
        $this->table = "amely_rule_permission";
        $conditions[] = [
            'key' => $type,
            'value' => "= 1",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= {$rule_id}",
            'operation' => 'AND'
        ];
        $conditions[] = [
            'key' => 'permission_id',
            'value' => "= {$permission->id}",
            'operation' => 'AND'
        ];
        $check = $this->searchObject($conditions, 0, 1);
        return $check;
    }

    public function getRuleByType($rule_id)
    {
        $this->table = "amely_rule";
        $conditions[] = [
            'key' => 'id',
            'value' => "= {$rule_id}",
            'operation' => ''
        ];
        return $this->searchObject($conditions, 0, 1);   
    }

    public function getRules()
    {
        $this->table = "amely_rule";
        return $this->searchObject(null, 0, 9999999);
    }

    public function getPermissions()
    {
        $this->table = "amely_permission";
        return $this->searchObject(null, 0, 9999999);
    }

    public function getPermissionsByRule($rule_id)
    {
        $this->table = "amely_rule_permission";
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= {$rule_id}",
            'operation' => ''
        ];
        return $this->searchObject($conditions, 0, 9999999);
    }

    public function getPermissionByPath($path)
    {
        $this->table = "amely_permission";
        $conditions[] = [
            'key' => 'path',
            'value' => "= '{$path}'",
            'operation' => ''
        ];
        return $this->searchObject($conditions, 0, 1);
    }
}