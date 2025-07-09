<?php
    use LiviuVoica\BoilerplateMVC\Controllers\UserController;

    $container = require __DIR__ . '/src/bootstrap/app.php';
    $controller = $container->get(UserController::class);

    dd([
        'timestamp' => date('Y-m-d H:i:s'),
    ]);