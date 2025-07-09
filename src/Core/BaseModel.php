<?php

namespace LiviuVoica\BoilerplateMVC\Core;

use LiviuVoica\BoilerplateMVC\Core\SQLiteORM;

class BaseModel
{
    protected SQLiteORM $orm;
    protected string $table;

    public function __construct(SQLiteORM $orm)
    {
        $this->orm = $orm;
        $this->table = '';
    }

    public function all(): ?array
    {
        return $this->orm->all($this->table);
    }
}