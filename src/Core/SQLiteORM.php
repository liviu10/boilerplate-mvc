<?php

namespace LiviuVoica\BoilerplateMVC\Core;

use Exception;
use PDO;

/**
 * Class SQLiteORM
 * 
 * Provides basic CRUD operations and schema inspection for SQLite databases using PDO.
 * Usage:
 * Instantiate with a PDO connection and a LogSystem instance for error logging.
 * Use the available methods to check table existence, create/drop tables, 
 * retrieve columns and types, and perform basic data manipulation.
 */
class SQLiteORM
{
    private const LOG_FILE_NAME = 'db_sqlite_orm_log';
    private PDO $connection;
    private LogSystem $log;

    public function __construct(SQLiteConnection $connection)
    {
        $this->connection = $connection->getConnection();
        $this->log = new LogSystem();
    }

    /**
     * Checks if a table exists in the database.
     * @param string $tableName The name of the table to check.
     * @return bool True if the table exists, otherwise false.
     */
    public function doesTableExist(string $tableName): bool
    {
        try {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=:tableName";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':tableName', $tableName, PDO::PARAM_STR);
            $stmt->execute();

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error checking if table exists: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Creates a table in the database.
     * @param string $createTableSql The SQL statement to create the table.
     * @param array $indexes An array of SQL statements to create indexes.
     * @return bool True if the table was created successfully, otherwise false.
     */
    public function createTable(string $sql, array $indexes = []): bool
    {
        try {
            $this->connection->exec($sql);
            foreach ($indexes as $indexSql) {
                $this->connection->exec($indexSql);
            }

            return true;
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => 'Error creating the table.',
                    'db_error' => $e->getMessage(),
                    'sql' => $sql,
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Delete the table.
     * @param string $tableName The name of the table to delete.
     * @return bool True if the table was deleted successfully, otherwise false.
     */
    public function dropTable(string $tableName): bool
    {
        try {
            $sql = "DROP TABLE IF EXISTS {$tableName}";
            $this->connection->exec($sql);

            return true;
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error dropping table '{$tableName}'.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Retrieves column information from the specified table.
     *
     * @param string $tableName The name of the table to fetch column info from.
     * @param bool $withTypes If true, returns an associative array with column names as keys and types as values.
     * If false, returns a simple array of column names.
     * @return array|null Returns the column data or null if an error occurs.
     */
    public function getColumns(string $tableName, bool $withTypes = false): ?array
    {
        try {
            $query = "PRAGMA table_info({$tableName})";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($withTypes) {
                $result = [];
                foreach ($columns as $column) {
                    $result[$column['name']] = $column['type'];
                }
                return $result;
            }

            return array_map(fn($column) => $column['name'], $columns);
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error retrieving columns from table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return null;
        }
    }

    /**
     * Retrieves all records from the specified table.
     * @param string $tableName The name of the table to fetch data from.
     * @return array|null Returns an array of records or null if an error occurs.
     */
    public function all(string $tableName): ?array
    {
        try {
            $query = "SELECT * FROM {$tableName}";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error retrieving records from the table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return null;
        }
    }

    /**
     * Retrieves a single record by its ID from the specified table.
     * @param string $tableName The name of the table to fetch data from.
     * @param int $id The ID of the record to retrieve.
     * @return array|null Returns the record as an associative array, or null if not found or an error occurs.
     */
    public function fetch(string $tableName, int $id): ?array
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM {$tableName} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error retrieving the record by ID from the table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return null;
        }
    }

    /**
     * Counts the number of rows in the specified table.
     * Optionally accepts conditions for filtering.
     * @param string $tableName The name of the table.
     * @param array $conditions Optional associative array of column => value pairs for filtering.
     * @return int|null Returns the count or null if an error occurs.
     */
    public function count(string $tableName, array $conditions = []): ?int
    {
        try {
            $query = "SELECT COUNT(id) as cnt FROM {$tableName}";
            $params = [];

            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $column => $value) {
                    $whereClauses[] = "{$column} = :{$column}";
                    $params[":{$column}"] = $value;
                }
                $query .= " WHERE " . implode(' AND ', $whereClauses);
            }

            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['cnt'] ?? 0);
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error counting rows in table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return null;
        }
    }

    /**
     * Retrieves paginated records from a table.
     *
     * @param string $tableName The table name.
     * @param int $page The current page number (1-based).
     * @param int $perPage Number of records per page.
     * @return array|null Returns an associative array with keys 'data' (records), 'total' (total rows), 'page', 'perPage', or null on error.
     */
    public function paginate(string $tableName, int $page = 1, int $perPage = 50): ?array
    {
        try {
            $offset = ($page - 1) * $perPage;

            // Get total count
            $countQuery = "SELECT COUNT(id) as cnt FROM {$tableName}";
            $countStmt = $this->connection->prepare($countQuery);
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            // Get paginated data
            $query = "SELECT * FROM {$tableName} LIMIT :limit OFFSET :offset";
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
            ];
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error paginating table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return null;
        }
    }

    /**
     * Inserts a new record into the specified table.
     * @param string $tableName The name of the table to insert data into.
     * @param array $payload The data to insert as an associative array.
     * @return bool Returns true if the insertion was successful, false otherwise.
     */
    public function save(string $tableName, array $payload): int|bool
    {
        try {
            $columns = implode(", ", array_keys($payload));
            $placeholders = ":" . implode(", :", array_keys($payload));

            $sql = "INSERT INTO {$tableName} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);

            foreach ($payload as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();

            return $stmt->rowCount() > 0 ? $this->connection->lastInsertId() : false;
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error inserting record into table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Inserts multiple records into the specified table in a single transaction.
     * @param string $tableName The name of the table to insert data into.
     * @param array $payload An array of associative arrays, each representing a record.
     * @return bool Returns true if all records were inserted successfully, false otherwise.
     */
    public function saveBulk(string $tableName, array $payload): bool
    {
        try {
            $this->connection->beginTransaction();

            $columns = array_keys($payload[0]);
            $columnsList = implode(", ", $columns);
            $placeholders = ":" . implode(", :", $columns);

            $sql = "INSERT INTO {$tableName} ({$columnsList}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);

            foreach ($payload as $record) {
                foreach ($record as $key => $value) {
                    $stmt->bindValue(":{$key}", $value);
                }
                $stmt->execute();
            }

            $this->connection->commit();

            return true;
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error inserting bulk records into table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Updates an existing record in the specified table based on the given ID and payload.
     * @param string $tableName The name of the table to update data in.
     * @param int $id The ID of the record to update.
     * @param array $payload The data to update as an associative array.
     * @return bool Returns true if the update was successful, false otherwise.
     */
    public function update(string $tableName, int $id, array $payload): bool
    {
        try {
            $setClause = [];
            foreach ($payload as $column => $value) {
                $setClause[] = "{$column} = :{$column}";
            }
            $setClause = implode(", ", $setClause);
            $sql = "UPDATE {$tableName} SET {$setClause} WHERE id = :id";
            $stmt = $this->connection->prepare($sql);

            foreach ($payload as $column => $value) {
                $stmt->bindValue(":{$column}", $value);
            }

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0 ? true : false;
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error updating record in table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Updates multiple records in bulk based on given IDs and payload.
     * @param string $tableName The name of the table to update.
     * @param array $ids Array of record IDs to update.
     * @param array $payload Associative array of columns and values to update.
     * @return bool True if all updates succeeded, false otherwise.
     */
    public function updateBulk(string $tableName, array $ids, array $payload): bool
    {
        try {
            $this->connection->beginTransaction();

            $setClause = [];
            foreach ($payload as $column => $value) {
                $setClause[] = "{$column} = :{$column}";
            }
            $setClause = implode(", ", $setClause);

            $inPlaceholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = "UPDATE {$tableName} SET {$setClause} WHERE id IN ({$inPlaceholders})";

            $stmt = $this->connection->prepare($sql);

            // Bind payload values
            foreach ($payload as $column => $value) {
                $stmt->bindValue(":{$column}", $value);
            }

            // Bind IDs (positional)
            foreach (array_values($ids) as $k => $id) {
                $stmt->bindValue($k + 1, $id, PDO::PARAM_INT);
            }

            $stmt->execute();

            $this->connection->commit();

            return true;
        } catch (Exception $e) {
            $this->connection->rollBack();

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error updating multiple records in table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Deletes a record from the specified table by ID.
     * @param string $tableName The name of the table to delete from.
     * @param int $id The ID of the record to delete.
     * @return bool Returns true if the record was deleted successfully, false otherwise.
     */
    public function delete(string $tableName, int $id): bool
    {
        try {
            $sql = "DELETE FROM {$tableName} WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0 ? true : false;
        } catch (Exception $e) {
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error deleting record from table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }

    /**
     * Deletes multiple records in bulk based on given IDs.
     * @param string $tableName The name of the table to delete from.
     * @param array $ids Array of record IDs to delete.
     * @return bool True if delete succeeded, false otherwise.
     */
    public function deleteBulk(string $tableName, array $ids): bool
    {
        try {
            $this->connection->beginTransaction();
            $inPlaceholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM {$tableName} WHERE id IN ({$inPlaceholders})";
            $stmt = $this->connection->prepare($sql);
            foreach (array_values($ids) as $k => $id) {
                $stmt->bindValue($k + 1, $id, PDO::PARAM_INT);
            }
            $stmt->execute();

            $this->connection->commit();

            return true;
        } catch (Exception $e) {
            $this->connection->rollBack();

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => "Error deleting multiple records from table: {$tableName}.",
                    'db_error' => $e->getMessage(),
                ],
                self::LOG_FILE_NAME
            );

            return false;
        }
    }
}
