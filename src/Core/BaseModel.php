<?php

namespace LiviuVoica\BoilerplateMVC\Core;

use LiviuVoica\BoilerplateMVC\Core\SQLiteORM;
use DateTime;
use Exception;

class BaseModel
{
    protected SQLiteORM $orm;
    protected string $table;
    protected int $perPage = 50;
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function __construct(SQLiteORM $orm)
    {
        $this->orm = $orm;
    }

    public function getTable(): string
    {
        return ucfirst($this->table);
    }

    public function getColumns(bool $withTypes = false): array|string
    {
        return $this->orm->getColumns($this->table, $withTypes);
    }

    public function all(): array|string
    {
        $records = $this->orm->all($this->table);

        if (!is_array($records)) {
            return $records;
        }

        return array_map(function ($record) {
            foreach ($this->casts as $key => $castType) {
                if (isset($record[$key])) {
                    $record[$key] = $this->applyCast($key, $record[$key]);
                }
            }
            return $this->applyHidden($record);
        }, $records);
    }

    public function fetch(int $id): array|string
    {
        $record = $this->orm->fetch($this->table, $id);

        if (!is_array($record)) {
            return $record;
        }

        foreach ($this->casts as $key => $castType) {
            if (isset($record[$key])) {
                $record[$key] = $this->applyCast($key, $record[$key]);
            }
        }

        return $this->applyHidden($record);
    }

    public function find(array $conditions): array|string
    {
        $record = $this->orm->find($this->table, $conditions);

        if (!is_array($record)) {
            return $record;
        }

        return array_map(function ($record) {
            foreach ($this->casts as $key => $castType) {
                if (isset($record[$key])) {
                    $record[$key] = $this->applyCast($key, $record[$key]);
                }
            }
            return $this->applyHidden($record);
        }, $record);
    }

    public function count(array $conditions = []): int|string
    {
        return $this->orm->count($this->table, $conditions);
    }

    public function paginate(int $page = 1, ?int $perPage = null): array|string
    {
        $perPage = $perPage ?? $this->perPage;
        $paginated = $this->orm->paginate($this->table, $page, $perPage);

        if (!is_array($paginated)) {
            return $paginated;
        }

        $paginated['data'] = array_map(function ($record) {
            foreach ($this->casts as $key => $castType) {
                if (isset($record[$key])) {
                    $record[$key] = $this->applyCast($key, $record[$key]);
                }
            }
            return $this->applyHidden($record);
        }, $paginated['data']);

        return $paginated;
    }

    public function save(array $payload): int|bool
    {
        $payload = $this->filterFillable($payload);

        return $this->orm->save($this->table, $payload);
    }

    public function update(int $id, array $payload): bool
    {
        $payload = $this->filterFillable($payload);

        return $this->orm->update($this->table, $id, $payload);
    }

    public function delete(int $id): bool
    {
        return $this->orm->delete($this->table, $id);
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function applyHidden(array $record): array
    {
        foreach ($this->hidden as $key) {
            unset($record[$key]);
        }

        return $record;
    }

    protected function applyCast(string $key, $value)
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        $cast = $this->casts[$key];

        if ($value === null) {
            return null;
        }

        if (str_starts_with($cast, 'datetime')) {
            $format = 'Y-m-d H:i:s';
            if (strpos($cast, ':') !== false) {
                $parts = explode(':', $cast, 2);
                $format = $parts[1];
            }
            if (empty($value)) {
                return null;
            }
            try {
                $dt = new DateTime($value);
                return $dt->format($format);
            } catch (Exception $e) {

                return $value;
            }
        }

        switch ($cast) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                return (bool)$value;
            case 'string':
                return (string)$value;
            case 'array':
                return (array)$value;
            case 'json':
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            case 'null':
                return null;
            default:
                return $value;
        }
    }
}
