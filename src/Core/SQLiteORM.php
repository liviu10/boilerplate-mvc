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
    private string $message;

    public function __construct(SQLiteConnection $connection, LogSystem $log)
    {
        $this->connection = $connection->getConnection();
        $this->log = $log;
    }

    /**
     * Retrieves column information from the specified table.
     *
     * @param string $tableName The name of the table to fetch column info from.
     * @param bool $withTypes If true, returns an associative array with column names as keys and types as values.
     * If false, returns a simple array of column names.
     * @return array|string Returns the column data or error message if an error occurs.
     */
    public function getColumns(string $tableName, bool $withTypes = false): array|string
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
            $this->message = 'An error occurred while retrieving the columns for the selected resource.';

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Retrieves all records from the specified table.
     * @param string $tableName The name of the table to fetch data from.
     * @return array|string Returns an array of records or string if an error occurs.
     */
    public function all(string $tableName): array|string
    {
        try {
            $query = "SELECT * FROM {$tableName}";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->message = 'An error occurred while retrieving the records for the selected resource.';
            
            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Retrieves a single record by its ID from the specified table.
     * @param string $tableName The name of the table to fetch data from.
     * @param int $id The ID of the record to retrieve.
     * @return array|bool|string Returns the record as an associative array, false
     * if the record does not exist or string if an error occurs.
     */
    public function fetch(string $tableName, int $id): array|bool|string
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM {$tableName} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {
            $this->message = "An error occurred while retrieving the record {$id}.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                    'id' => $id,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Finds and returns the first record that matches the given conditions.
     * @param string $tableName Table to search in.
     * @param array  $conditions Associative array column => value.
     * @return array|bool|string First matching row, false if the record does not exist
     * or string if an error occurs.
     */
    public function find(string $tableName, array $conditions): array|bool|string
    {
        try {
            $columnsInfo = $this->getColumns($tableName, true) ?? [];

            $whereParts = [];
            $bindings = [];
            $index = 0;

            foreach ($conditions as $cond) {
                $column = $cond[0];
                if (count($cond) === 2) {
                    $operator = '=';
                    $value = $cond[1];
                } else {
                    $operator = strtoupper($cond[1]);
                    $value = $cond[2];
                }

                $param = ':p' . $index++;
                $whereParts[] = "{$column} {$operator} {$param}";
                $bindings[$param] = $value;
            }

            $sql = "SELECT * FROM {$tableName}";
            if ($whereParts) {
                $sql .= " WHERE " . implode(' AND ', $whereParts);
            }
            $sql .= " LIMIT 1";

            $stmt = $this->connection->prepare($sql);

            $index = 0;
            foreach ($conditions as $cond) {
                $column = $cond[0];
                $value = (count($cond) === 2) ? $cond[1] : $cond[2];

                $type = PDO::PARAM_STR;
                if (is_int($value) || (isset($columnsInfo[$column]) && stripos($columnsInfo[$column], 'INT') !== false)) {
                    $type = PDO::PARAM_INT;
                }

                $param = ':p' . $index++;
                $stmt->bindValue($param, $value, $type);
            }

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->message = "An error occurred while finding the record in the selected resource.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                    'conditions' => $conditions,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Counts the number of rows in the specified table.
     * Optionally accepts conditions for filtering.
     * @param string $tableName The name of the table.
     * @param array $conditions Optional associative array of column => value pairs for filtering.
     * @return int|string Returns the count or string if an error occurs.
     */
    public function count(string $tableName, array $conditions = []): int|string
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
            $this->message = "An error occurred while counting the records for the selected resource.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Retrieves paginated records from a table.
     *
     * @param string $tableName The table name.
     * @param int $page The current page number (1-based).
     * @param int $perPage Number of records per page.
     * @return array|string Returns an associative array with keys
     * 'data' (records), 'total' (total rows), 'page', 'perPage', or string if error occurs.
     */
    public function paginate(string $tableName, int $page = 1, int $perPage = 50): array|string
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
            $this->message = "An error occurred while retrieving the pagination for the selected resource.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Inserts a new record into the specified table.
     * @param string $tableName The name of the table to insert data into.
     * @param array $payload The data to insert as an associative array.
     * @return bool|string Returns true if the insertion was successful, string if error occurs.
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
            $this->message = "An error occurred while saving the record in the selected resource.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Updates an existing record in the specified table based on the given ID and payload.
     * @param string $tableName The name of the table to update data in.
     * @param int $id The ID of the record to update.
     * @param array $payload The data to update as an associative array.
     * @return bool|string Returns true if the update was successful, string if error occurs.
     */
    public function update(string $tableName, int $id, array $payload): bool|string
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
            $this->message = "An error occurred while updating the record in the selected resource.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    /**
     * Deletes a record from the specified table by ID.
     * @param string $tableName The name of the table to delete from.
     * @param int $id The ID of the record to delete.
     * @return bool|string Returns true if the record was deleted successfully, string if error occurs.
     */
    public function delete(string $tableName, int $id): bool|string
    {
        try {
            $sql = "DELETE FROM {$tableName} WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0 ? true : false;
        } catch (Exception $e) {
            $this->message = "An error occurred while deleting the record in the selected resource.";

            $this->log->handle(
                LogSystem::ERROR_LEVEL,
                [
                    'message' => $this->message,
                    'db_error' => $e->getMessage(),
                    'table' => $tableName,
                ],
                self::LOG_FILE_NAME
            );

            return $this->constructMessage($this->message);
        }
    }

    private function constructMessage(string $message): string
    {
        return "{$message} Please try again and if the problem persist you can contact the administrator.";
    }
}
