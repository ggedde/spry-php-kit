<?php declare(strict_types = 1);
/**
 * This file is to handle The Job Class
 */

namespace SpryPhp\Model;

use SpryPhp\Provider\Db;
use SpryPhp\Provider\Functions;

/**
 * Job Instance
 */
class DbItem
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
     * Created At Timestamp
     *
     * @var string $createdAtFormatted
     */
    public string $createdAtFormatted = '';

    /**
     * Updated At Timestamp
     *
     * @var string $updatedAt
     */
    public string $updatedAt = '';

    /**
     * Updated At Timestamp
     *
     * @var string $updatedAt
     */
    public string $updatedAtFormatted = '';

    /**
     * Database Table
     *
     * @var string $dbTable
     */
    protected string $dbTable = '';

    /**
     * The DB Schema
     *
     * @var array $schema
     */
    protected array $dbSchema = [];

    /**
     * Construct the Obj
     *
     * @param object|string $obj - Object of Obj
     */
    public function __construct(object|string $obj)
    {
        if (is_string($obj) && !empty($obj)) {
            $objId = $obj;
            $obj = Db::get($this->dbTable, ['*'], ['id' => $objId]);
            if (empty($obj)) {
                Functions::abort('Database Error: Cannot find Item ('.$objId.') in '.ucwords($this->dbTable));
            }
        }

        if (empty($this->id)) {
            $this->id = Db::newUuid();
        }

        foreach (array_keys((array) $obj) as $key) {
            $camelKey = Functions::convertToCamelCase($key);
            if (in_array($camelKey, array_keys((array) get_object_vars($this)), true)) {
                $this->$camelKey = is_string($obj->$key) ? Functions::esc($obj->$key) : (is_null($obj->$key) ? '' : $obj->$key);
            }
        }

        $this->createdAt = !empty($this->createdAt) ? $this->createdAt : gmdate('Y-m-d H:i:s');
        $this->updatedAt = !empty($this->updatedAt) ? $this->updatedAt : gmdate('Y-m-d H:i:s');

        $this->createdAtFormatted = gmdate('M j, y', strtotime('-7 hours', strtotime($this->createdAt)));
        $this->updatedAtFormatted = gmdate('M j, y', strtotime('-7 hours', strtotime($this->updatedAt)));
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
     * Get the Columns from the Database
     *
     * @return array
     */
    public function columns(): array
    {
        // $ref = new \ReflectionClass(static::class);
        // $props = $ref->getProperties();
        // foreach ($props as $prop) {
        //     $item = (object) [
        //         'name' => $prop->getName(),
        //         'type' => $prop->getType()->getName(),
        //     ];
        //     foreach ($prop->getAttributes() as $attr) {
        //         if ($attr->getName() === 'App\ORM') {
        //             $item->orm = $attr->newInstance();
        //             if (is_null($item->orm->type) && !empty($type)) {
        //                 $item->orm->type = !empty($type) ? $type : 'string';
        //             }
        //         }
        //         if ($attr->getName() === 'App\Validate') {
        //             $item->validate = $attr->newInstance();
        //         }
        //     }
        //     echo '<pre>';print_r( $item );echo '</pre>'; // phpcs:ignore
        // }
        // dd($item);

        $columns = [];
        $result = Db::query('SHOW COLUMNS FROM '.$this->dbTable);
        if ($result) {
            while ($row = mysqli_fetch_array($result)) {
                if (preg_match('/int|bool/', strtolower($row['Type']))) {
                    $type = 'int';
                } elseif (preg_match('/float/', strtolower($row['Type']))) {
                    $type = 'float';
                } else {
                    $type = 'string';
                }

                preg_match('/varchar\(([0-9]*)\)/', strtolower($row['Type']), $varcharMatch);

                if (!empty($varcharMatch[0])) {
                    if (!empty($varcharMatch[1])) {
                        $length = intval($varcharMatch[1]);
                    } else {
                        $length = 255;
                    }
                } elseif (strtolower($row['Type']) === 'text') {
                    $length = 60000;
                }

                if (preg_match('/int|bool/', strtolower($row['Type']))) {
                    $type = 'int';
                } elseif (preg_match('/float/', strtolower($row['Type']))) {
                    $type = 'float';
                } else {
                    $type = 'string';
                }

                $columns[] = (object) [
                    'name' => $row['Field'],
                    'db_type' => strtoupper($row['Type']),
                    'type' => $type,
                    'length' => $length,
                    'null' => !empty($row['Null']) && strtolower($row['Null']) === 'yes' ? true : false,
                    'default' => $row['Default'],
                    'index' => !empty($row['Key']) && in_array($row['Key'], ['MUL', 'PRI'], true) ? true : (!empty($row['Key']) && $row['Key'] === 'UNI' ? 'unique' : false),
                ];
            }
        }

        return $columns;
    }

    /**
     * Insert Data
     *
     * @return bool
     */
    public function insert(): bool
    {
        $dataSet = (object) [];

        $columns = $this->columns();

        foreach (array_keys(get_object_vars($this)) as $key) {
            $snakeKey = Functions::convertToSnakeCase($key);
            if (in_array($snakeKey, array_column($columns, 'name'), true) && !in_array($snakeKey, ['created_at', 'updated_at'], true)) {
                $dataSet->$snakeKey = $this->$key;
            }
        }

        return Db::insert($this->dbTable, $dataSet);
    }

    /**
     * Update Data
     *
     * @param array|object $data
     *
     * @return bool
     */
    public function update(array|object $data): bool
    {
        return Db::update($this->dbTable, $data, ['id' => $this->id]);
    }
}
