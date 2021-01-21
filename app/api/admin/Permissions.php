<?php

namespace app\api\admin;

use app\lib\Model;

class Permissions extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function node($node)
    {
        $stmt =  $this->select("account_USERNAME=?", [ $this->user() ]);
        $permissions = $stmt->fetchObject()->account_PERMISSIONS;

        if($permissions == '*')
        {
            return true;
        }

        $permissions = explode(" ", $permissions);
        $exploded = [];
        foreach ($permissions as $permission)
        {
            $exp = explode(".", $permission);
            array_push($exploded, $exp[0]);
        }

        return in_array($node, $exploded);
    }

    public function parent($node, $parent)
    {
        $stmt = $this->select("account_USERNAME=?", [ $this->user() ]);
        $permissions = $stmt->fetchObject()->account_PERMISSIONS;

        if($permissions == "*")
        {
            return true;
        }

        $permissions = explode(" ", $permissions);

        if(in_array("{$node}.*", $permissions))
        {
            return true;
        }

        return in_array("{$node}.{$parent}", $permissions);
    }

    private function select($query, $data)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_accounts` WHERE ".$query);
        $stmt->execute($data);
        return $stmt;
    }

    private function user()
    {
        $account = new Accounts();
        return $account->username();
    }

}