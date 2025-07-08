<?php

namespace LiviuVoica\BoilerplateMVC\Core;

use LiviuVoica\BoilerplateMVC\Utils\LogSystem;
use Exception;
use PDO;

class SQLiteConnection
{
    private const LOG_FILE_NAME = 'db_sqlite_log';
    private const DB_FILE = __DIR__ . '/../database/family-tree-db.sqlite';
    private LogSystem $log;
    private string $dbFile;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $this->log = new LogSystem();
        $this->dbFile = self::DB_FILE;
        $this->ensureDbExists();
        $this->ensureWritable();
        $this->connect();
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
     * Ensures that the database file exists. If it doesn't, it creates it.
     * @return void
     */
    private function ensureDbExists(): void
    {
        if (!file_exists($this->dbFile)) {
            try {
                $dir = dirname($this->dbFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($this->dbFile, '');

                $this->log->handleLog(
                    LogSystem::INFO_LEVEL,
                    [
                        'message' => "Database file was successfully created at {$this->dbFile}",
                    ],
                    self::LOG_FILE_NAME
                );
            } catch (Exception $e) {
                $this->log->handleLog(
                    LogSystem::ERROR_LEVEL,
                    [
                        'message' => 'Error creating the database file: ' . $e->getMessage(),
                    ],
                    self::LOG_FILE_NAME
                );
            }
        }
    }

    /**
     * Ensures that the database file is writable.
     * @return void
     */
    private function ensureWritable(): void
    {
        if (!is_writable($this->dbFile)) {
            if (!chmod($this->dbFile, 0777)) {
                $this->log->handleLog(
                    LogSystem::ERROR_LEVEL,
                    [
                        'message' => 'Failed to set write permissions on the file. Please check the log file: ' . self::LOG_FILE_NAME . ' for more details.',
                        'db_error' => 'File does not have write permissions.',
                    ],
                    self::LOG_FILE_NAME
                );
            }
        }
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
            $this->log->handleLog(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => 'Error connecting to the database. Please check the log file: ' . self::LOG_FILE_NAME . ' for more details.',
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );
        }
    }

    /**
     * Destructor to ensure PDO connection is closed.
     */
    public function __destruct()
    {
        $this->pdo = null;
    }
}
