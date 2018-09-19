<?php

namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;
use Tuupola\Base62;
use Psr\Log\LogLevel;

class Session extends \Spot\Entity
{
    protected static $table = "session";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "username" => ["type" => "string", "length" => 255],
            "jti" => ["type" => "string", "length" => 255],
            "devid" => ["type" => "string", "length" => 255],
            "device_name" => ["type" => "string", "length" => 255],
            "latitude"   => ["type" => "float"],
            "longitude"   => ["type" => "float"],
            "last_login_at"   => ["type" => "datetime", "value" => new \DateTime(), "required" => true]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->last_login_at = new \DateTime();
        });
    }

    public function timestamp()
    {
        return $this->updated_at->getTimestamp();
    }

    public function etag()
    {
        return md5($this->username . $this->timestamp());
    }

    public function login($username, $password)
    {
        $verified = false;

        if ($this->username == $username) {
            $verified = password_verify($password, $this->password_hash);
        }
        return $verified;
    }

    public function clear()
    {
        $this->data([
            "uid" => null,
            "username" => null,
            "full_name" => null,
            "phone" => null,
            "parent_phone" => null,
            "auth_key" => null,
            "password_hash" => null,
            "password_reset_token" => null,
            "email" => null,
            "status" => 0

        ]);
    }
}
