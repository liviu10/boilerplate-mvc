<?php

namespace LiviuVoica\BoilerplateMVC\Models;

use LiviuVoica\BoilerplateMVC\Core\BaseModel;
use LiviuVoica\BoilerplateMVC\Core\SQLiteORM;

class User extends BaseModel
{
    protected string $table;

    public function __construct(SQLiteORM $orm)
    {
        parent::__construct($orm);
        $this->table = 'users';
    }
}