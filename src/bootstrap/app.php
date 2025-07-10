<?php

    require dirname(__DIR__, 2) . '/vendor/autoload.php';
    require_once __DIR__ . '/../Utilities/helpers.php';

    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->load();

    use DI\ContainerBuilder;
    use function DI\create;
    use function DI\get;

    // Application core
    use LiviuVoica\BoilerplateMVC\Core\LogSystem;
    use LiviuVoica\BoilerplateMVC\Core\SQLiteConnection;
    use LiviuVoica\BoilerplateMVC\Core\SQLiteORM;
    use LiviuVoica\BoilerplateMVC\Core\Validation;

    // Inject dependencies
    $builder = new ContainerBuilder();
    $builder->addDefinitions([
        LogSystem::class => create(),
        SQLiteConnection::class => create(),
        Validation::class => create(),
        SQLiteORM::class => create()->constructor(
            get(SQLiteConnection::class),
            get(LogSystem::class)
        ),
    ]);

    return $builder->build();
