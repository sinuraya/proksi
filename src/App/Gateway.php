<?php

namespace App;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

class Gateway extends \Spot\Entity
{
    protected static $table = "gateway";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "path" => ["type" => "string", "length" => 255, "unique" => true, "required" => true],
            "endpoint" => ["type" => "string", "length" => 255],
            "permission" => ["type" => "string", "length" => 255],
            "status" => ["type" => "smallint", "length" => 6, "required" => true],
            "created_at"   => ["type" => "datetime", "value" => new \DateTime(), "required" => true],
            "updated_at"   => ["type" => "datetime", "value" => new \DateTime(), "required" => true]
        ];
    }

    public static function events(EventEmitter $emitter)
    {
        $emitter->on("beforeUpdate", function (EntityInterface $entity, MapperInterface $mapper) {
            $entity->updated_at = new \DateTime();
        });
    }
    public function timestamp()
    {
        return $this->updated_at->getTimestamp();
    }

    public function etag()
    {
        return md5($this->path . $this->timestamp());
    }

    public function clear()
    {
        $this->data([
            "path" => null,
            "permission" => null,
            "status" => 0
        ]);
    }
}
