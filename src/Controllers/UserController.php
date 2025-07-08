<?php

namespace LiviuVoica\BoilerplateMVC\Controllers;

use LiviuVoica\BoilerplateMVC\Core\SQLiteConnection;
use LiviuVoica\BoilerplateMVC\Models\User;
use LiviuVoica\BoilerplateMVC\Utils\LogSystem;
use PDO;

class UserController
{
    private PDO $connection;
    private LogSystem $log;
    private User $user;

    public function __construct(SQLiteConnection $connection)
    {
        $this->connection = $connection->getConnection();
        $this->log = new LogSystem();
        $this->user = new User();
    }
}