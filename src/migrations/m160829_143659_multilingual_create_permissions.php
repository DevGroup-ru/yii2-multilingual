<?php

use yii\db\Migration;

class m160829_143659_multilingual_create_permissions extends Migration
{
    protected $permissions = [
        'multilingual-view-context' => '',
        'multilingual-create-context' => '',
        'multilingual-edit-context' => '',
        'multilingual-delete-context' => '',
        //
        'multilingual-view-language' => '',
        'multilingual-create-language' => '',
        'multilingual-edit-language' => '',
        'multilingual-delete-language' => '',
        //
        'multilingual-view-country-language' => '',
        'multilingual-create-country-language' => '',
        'multilingual-edit-country-language' => '',
        'multilingual-delete-country-language' => '',
    ];

    const ADMIN_ROLE_NAME = 'MultilingualAdministrator';

    protected function error($message)
    {
        $length = strlen($message);
        echo "\n" . str_repeat('=', $length) . "\n" . $message . "\n" . str_repeat('=', $length) . "\n\n";
    }

    public function up()
    {
        $auth = Yii::$app->authManager;
        if ($auth === null) {
            $this->error('Please configure AuthManager before');
            return false;
        }
        try {
            $role = $auth->createRole(self::ADMIN_ROLE_NAME);
            $role->description = '';
            $auth->add($role);
            foreach ($this->permissions as $name => $description) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                $auth->addChild($role, $permission);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
        return true;
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        if ($auth !== null) {
            $role = $auth->getRole(self::ADMIN_ROLE_NAME);
            if ($role !== null) {
                $auth->remove($role);
                foreach ($this->permissions as $name => $description) {
                    $permission = $auth->getPermission($name);
                    if ($permission === null) {
                        continue;
                    }
                    $auth->remove($permission);
                }
            }
        }
    }
}
