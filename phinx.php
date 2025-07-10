<?php

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/src/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/src/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'migrations',
        'default_environment' => $_ENV['APP_ENV'],

        'development' => [
            'adapter' => 'sqlite',
            'name' => __DIR__ . '/src/database/boilerplate-db-dev',
        ],

        'testing' => [
            'adapter' => 'sqlite',
            'name' => __DIR__ . '/src/database/boilerplate-db-test',
        ],

        'production' => [
            'adapter' => 'sqlite',
            'name' => __DIR__ . '/src/database/boilerplate-db-prod',
        ],
    ],
    'version_order' => 'creation',
];
