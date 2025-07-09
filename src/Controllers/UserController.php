<?php

namespace LiviuVoica\BoilerplateMVC\Controllers;

use LiviuVoica\BoilerplateMVC\Models\User;
use LiviuVoica\BoilerplateMVC\Core\LogSystem;
use PDO;

class UserController
{
    private User $user;
    private LogSystem $log;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->log = new LogSystem();
    }

    public function index()
    {
        $data = $this->user->all();

        return $data;
    }
}