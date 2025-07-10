<?php

namespace LiviuVoica\BoilerplateMVC\Models;

use LiviuVoica\BoilerplateMVC\Core\BaseModel;
use LiviuVoica\BoilerplateMVC\Core\LogSystem;
use LiviuVoica\BoilerplateMVC\Core\SQLiteConnection;
use LiviuVoica\BoilerplateMVC\Core\SQLiteORM;

class User extends BaseModel
{
    protected string $table = 'users';
    protected int $perPage = 100;
    protected array $fillable = [
        'name',
        'email',
    ];
    protected array $hidden = [
        'password',
    ];
    protected array $casts = [
        'created_at' => 'datetime:d.m.Y H:i',
        'updated_at' => 'datetime:d.m.Y H:i',
    ];

    public function __construct()
    {
        $connection = new SQLiteConnection();
        $log = new LogSystem();
        $orm = new SQLiteORM($connection, $log);

        parent::__construct($orm);
    }
}