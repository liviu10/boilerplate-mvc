<?php

namespace LiviuVoica\BoilerplateMVC\Controllers;

use LiviuVoica\BoilerplateMVC\Core\Validation;
use LiviuVoica\BoilerplateMVC\Models\User;

class AuthController
{
    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }
}
