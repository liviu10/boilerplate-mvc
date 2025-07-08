<?php

    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->load();

    use DI\ContainerBuilder;
    use function DI\create;

    use LiviuVoica\BoilerplateMVC\Core\SQLiteConnection;
    use LiviuVoica\BoilerplateMVC\Controllers\UserController;

    // Inject dependencies
    $builder = new ContainerBuilder();
    $builder->addDefinitions([
        UserController::class => create()->constructor(new SQLiteConnection())
    ]);

    return $builder->build();
