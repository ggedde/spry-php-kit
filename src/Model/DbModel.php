<?php declare(strict_types=1);
/**
 * This file is to handle The DB Model
 */

namespace SpryPhp\Model;

use Exception;
use ReflectionClass;
use ReflectionProperty;
use SpryPhp\Provider\Db;
use SpryPhp\Provider\Functions;

/**
 * DB Model Instance
 */
class DbModel
{
    /**
     * Id
     *
     * @var string $id
     */
    public string $id = '';

    /**
     * Created At Timestamp
     *
     * @var string $createdAt
     */
    public string $createdAt = '';

    /**
     * Created At Timestamp Formatted
     *
     * @var string $createdAtFormatted
     *
     * @uses APP_DATETIME_FORMAT
     */
    public string $createdAtFormatted = '';

    /**
     * Created At Timestamp Local
     *
     * @var string $createdAtLocal
     *
     * @uses APP_DATETIME_OFFSET
     */
    public string $createdAtLocal = '';

    /**
     * Created At Timestamp Local Formatted
     *
     * @var string $createdAtLocalFormatted
     *
     * @uses APP_DATETIME_OFFSET
     * @uses APP_DATETIME_FORMAT
     */
    public string $createdAtLocalFormatted = '';

    /**
     * Updated At Timestamp
     *
     * @var string $updatedAt
     */
    public string $updatedAt = '';

    /**
     * Updated At Timestamp Formatted
     *
     * @var string $updatedAtFormatted
     *
     * @uses APP_DATETIME_FORMAT
     */
    public string $updatedAtFormatted = '';

    /**
     * Updated At Timestamp Local
     *
     * @var string $updatedAtLocal
     *
     * @uses APP_DATETIME_OFFSET
     */
    public string $updatedAtLocal = '';

    /**
     * Updated At Timestamp Local Formatted
     *
     * @var string $updatedAtLocalFormatted
     *
     * @uses APP_DATETIME_OFFSET
     * @uses APP_DATETIME_FORMAT
     */
    public string $updatedAtLocalFormatted = '';

    /**
     * The DB Table
     *
     * @var string $dbTable
     */
    protected string $dbTable = '';

    /**
     * Construct the Obj
     *
     * @param object|array<string,mixed>|string|null $obj
     * - Pass Object to Set Values of DB Item
     * - Pass Array to use as a Where Clause to Get the Item from the DB
     * - Pass String to use as the ID to Get the Item from the DB
     * - Pass NULL to use as an Empty Item as Skeleton
     *
     * @throws Exception
     */
    public function __construct(object|array|string|null $obj = null)
    {
        // Try By ID
        if (is_string($obj) && !empty($obj)) {
            $objId = $obj;
            $obj = Db::get($this->dbTable, ['*'], ['id' => $objId]);
            if (empty($obj)) {
                throw new Exception(sprintf('SpryPHP Database Error: Cannot find Item: (%s) in Table: %s', $objId, ucwords($this->dbTable)));
            }
        }

        // Try Where Clause
        if (is_array($obj) && !empty($obj)) {
            $obj = Db::get($this->dbTable, ['*'], $obj); // @phpstan-ignore argument.type
            if (empty($obj)) {
                throw new Exception(sprintf('SpryPHP Database Error: Cannot find Item in Table: %s', ucwords($this->dbTable)));
            }
        }

        // Create an empty Skeleton Object if Null.
        if (is_null($obj)) {
            $obj = (object) [];
        }

        if (empty($this->id)) {
            $this->id = Functions::newUuid();
        }

        foreach (array_keys((array) $obj) as $key) {
            $camelKey = Functions::formatCamelCase($key);
            if (in_array($camelKey, $this->getFields(), true)) {
                $this->$camelKey = is_string($obj->$key) ? Functions::escString($obj->$key) : (is_null($obj->$key) ? '' : $obj->$key);
            }
        }

        // Create Timestamps.
        $createdAtTime = strtotime($this->createdAt) ?: null;
        $updatedAtTime = strtotime($this->updatedAt) ?: null;
        $dateTimeFormattedFormat = defined('APP_DATETIME_FORMAT') ? Functions::constantString('APP_DATETIME_FORMAT') : 'M j, y g:ia';

        // Create Formatted Options.
        $this->createdAt          = $this->createdAtLocal = gmdate('Y-m-d H:i:s', $createdAtTime);
        $this->updatedAt          = $this->updatedAtLocal = gmdate('Y-m-d H:i:s', $updatedAtTime);
        $this->createdAtFormatted = $this->createdAtLocalFormatted = gmdate($dateTimeFormattedFormat, $createdAtTime);
        $this->updatedAtFormatted = $this->updatedAtLocalFormatted = gmdate($dateTimeFormattedFormat, $updatedAtTime);

        // Convert to Local Time.
        if (defined('APP_DATETIME_OFFSET')) {
            $createdAtTimeLocal            = strtotime(Functions::constantString('APP_DATETIME_OFFSET'), $createdAtTime) ?: null;
            $updatedAtTimeLocal            = strtotime(Functions::constantString('APP_DATETIME_OFFSET'), $updatedAtTime) ?: null;
            $this->createdAtLocal          = gmdate('Y-m-d H:i:s', $createdAtTimeLocal);
            $this->updatedAtLocal          = gmdate('Y-m-d H:i:s', $updatedAtTimeLocal);
            $this->createdAtLocalFormatted = gmdate($dateTimeFormattedFormat, $createdAtTimeLocal);
            $this->updatedAtLocalFormatted = gmdate($dateTimeFormattedFormat, $updatedAtTimeLocal);
        }
    }

    /**
     * Get the Columns from the Database
     *
     * @return object[]
     */
    public function getColumns(): array
    {
        $columns = [];
        $result = Db::query('SHOW COLUMNS FROM '.$this->dbTable);
        if ($result && !is_bool($result)) {
            while ($row = mysqli_fetch_array($result)) {
                if (isset($row['Type']) && is_string($row['Type'])) {
                    if (preg_match('/int|bool/i', $row['Type'])) {
                        $type = 'int';
                    } elseif (preg_match('/float/i', $row['Type'])) {
                        $type = 'float';
                    } elseif (preg_match('/int|bool/i', $row['Type'])) {
                        $type = 'int';
                    } else {
                        $type = 'string';

                        preg_match('/varchar\(([0-9]*)\)/i', strtolower($row['Type']), $varcharMatch);

                        if (!empty($varcharMatch[0])) {
                            if (!empty($varcharMatch[1])) {
                                $length = intval($varcharMatch[1]);
                            } else {
                                $length = 255;
                            }
                        } elseif (strtolower($row['Type']) === 'text') {
                            $length = 60000;
                        }
                    }

                    $columns[] = (object) [
                        'name' => $row['Field'],
                        'db_type' => strtoupper($row['Type']),
                        'type' => $type,
                        'length' => isset($length) ? $length : null,
                        'null' => !empty($row['Null']) && is_string($row['Null']) && strtolower($row['Null']) === 'yes' ? true : false,
                        'default' => $row['Default'],
                        'index' => !empty($row['Key']) && in_array($row['Key'], ['MUL', 'PRI'], true) ? true : (!empty($row['Key']) && $row['Key'] === 'UNI' ? 'unique' : false),
                    ];
                }
            }
        }

        return $columns;
    }

    /**
     * Get the Table Name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->dbTable;
    }

    /**
     * Get the Object Variable Names
     *
     * @param bool $publicOnly
     *
     * @return string[]
     */
    public function getFields(bool $publicOnly = true): array
    {
        $reflection = new ReflectionClass($this);

        if ($publicOnly) {
            return array_column($reflection->getProperties(ReflectionProperty::IS_PUBLIC), 'name');
        }

        return array_column($reflection->getProperties(), 'name');
    }

    /**
     * Delete the Item
     *
     * @return bool
     */
    public function delete(): bool
    {
        return Db::delete($this->dbTable, ['id' => $this->id]);
    }

    /**
     * Insert Data
     *
     * @return bool
     */
    public function insert(): bool
    {
        $dataSet = (object) [];

        $columns = $this->getColumns();

        foreach ($this->getFields(false) as $key) {
            $snakeKey = Functions::formatSnakeCase($key);
            if (in_array($snakeKey, array_column($columns, 'name'), true) && !in_array($snakeKey, ['created_at', 'updated_at'], true)) {
                $dataSet->$snakeKey = $this->$key;
            }
        }

        return Db::insert($this->dbTable, $dataSet);
    }

    /**
     * Update Data
     *
     * @return bool
     */
    public function update(): bool
    {
        $dataSet = (object) [];

        $columns = $this->getColumns();

        foreach ($this->getFields(false) as $key) {
            $snakeKey = Functions::formatSnakeCase($key);
            if (in_array($snakeKey, array_column($columns, 'name'), true) && !in_array($snakeKey, ['id', 'created_at', 'updated_at'], true)) {
                $dataSet->$snakeKey = $this->$key;
            }
        }

        return Db::update($this->dbTable, $dataSet, ['id' => $this->id]);
    }
}
