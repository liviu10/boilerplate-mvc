<?php

namespace LiviuVoica\BoilerplateMVC\Core;

use Exception;
use PDO;

/**
 * Class SQLiteConnection
 * 
 * Manages a connection to a SQLite database using PDO.
 * Usage:
 * Inject a LogSystem instance into the constructor, then call getConnection()
 * to retrieve the PDO instance for further operations.
 */
class SQLiteConnection
{
    private const LOG_FILE_NAME = 'db_sqlite_log';
    private LogSystem $log;
    private string $dbFile;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $this->log = new LogSystem();
        $phinxConfig = require __DIR__ . '/../../phinx.php';
        $this->setCurrentDatabase($phinxConfig);
    }

    /**
     * Returns the PDO connection instance.
     * @return PDO|null
     */
    public function getConnection(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Get the name of the database for the current environment
     * and make sure that it has permissions.
     * @return void
     */
    private function setCurrentDatabase(array $phinxConfig): void
    {
        $env = $_ENV['APP_ENV'] ?? 'development';

        if (!isset($phinxConfig['environments'][$env]['name'])) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Missing database path for environment '{$env}' in phinx configuration.",
                    'phinx_config' => $phinxConfig,
                ],
                self::LOG_FILE_NAME
            );

            return;
        }

        $path = $phinxConfig['environments'][$env]['name'];

        if (!str_starts_with($path, '/')) {
            $path = __DIR__ . '/../' . ltrim($path, '/');
        }

        $this->dbFile = "{$path}.sqlite3";

        if (!is_writable($this->dbFile)) {
            if (!chmod($this->dbFile, 0777)) {
                $this->log->handle(
                    LogSystem::ERROR_LEVEL,
                    [
                        'message' => 'Failed to set write permissions on the file.',
                    ],
                    self::LOG_FILE_NAME
                );
            }
        }

        $this->connect();
    }

    /**
     * Establishes a connection to the SQLite database.
     * @return void
     */
    private function connect(): void
    {
        try {
            $this->pdo = new PDO("sqlite:{$this->dbFile}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => 'Error connecting to the database.',
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );
        }
    }
}
