<?php
/**
 * This file is to handle the DB
 */

namespace SpryPhp\Provider;

use Exception;

/**
 * Class for managing DB requests
 */
class Db
{
    /**
     * The DB Connection
     *
     * @var object $db
     */
    private static ?object $db = null;

    /**
     * The Last Query
     *
     * @var string $lastQuery
     */
    private static string $lastQuery = '';

    /**
     * The Last Total Count
     *
     * @var int $lastTotal
     */
    private static int $lastTotal = 0;

    /**
     * Initiate the DB
     *
     * @throws Exception
     *
     * @return void
     */
    public static function connect()
    {
        // MySQL Variables
        $hostname = getenv('DB_HOST');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $database = getenv('DB_NAME');
        $socket   = null;

        if (strpos($hostname, ':')) {
            $serverInfo = explode(':', $hostname);
            $hostname = $serverInfo[0];
            $socket = $serverInfo[1];
        }

        // Connect to MySQL
        self::$db = mysqli_connect($hostname, $username, $password, null, null, $socket);

        // Convert Int and Floats back to PHP.
        mysqli_options(self::$db, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

        // Check if connection is Successful or not
        if (!self::$db) {
            throw new Exception(sprintf('Server Connection failed: %s', mysqli_connect_error()), 1);
        }

        if (!mysqli_select_db(self::$db, $database)) {
            throw new Exception(sprintf('Database Connection failed: %s', mysqli_connect_error()), 1);
        }
    }

    /**
     * Get Tables
     *
     * @return array
     */
    public static function tables(): array
    {
        $tables = [];

        $result = self::query('SHOW TABLES');
        while ($row = mysqli_fetch_row($result)) {
            if (!empty($row[0]) && is_string($row[0])) {
                $tables[] = $row[0];
            }
        }

        return $tables;
    }

    /**
     * Query Db
     *
     * @param string $sql
     *
     * @return \mysqli_result|bool|null
     */
    public static function query(string $sql): \mysqli_result|bool|null
    {
        // Make sure we are connected.
        if (!self::$db) {
            self::connect();
        }

        try {
            self::$lastQuery = $sql;
            $result = mysqli_query(self::$db, $sql);
            if ($result) {
                return $result;
            }

            $error = mysqli_error(self::$db);
            if ($error) {
                throw new Exception(sprintf('Database Error: %s', $error), 1);
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('Database Error: %s', $e->getMessage()), 1);
        }

        return null;
    }

    /**
     * Get Single Item
     *
     * @param string     $table
     * @param array|null $columns
     * @param array|null $where
     * @param array|null $order
     *
     * @return object|null
     */
    public static function get(string $table, ?array $columns = null, ?array $where = null, ?array $order = null): ?object
    {
        $data = self::select($table, $columns, null, $where, null, $order, [1]);

        if (!empty($data[0])) {
            return (object) $data[0];
        }

        return null;
    }

    /**
     * Select all Entries
     *
     * @param string     $table
     * @param array|null $columns
     * @param array|null $join
     * @param array|null $where
     * @param array|null $group
     * @param array|null $order
     * @param array|null $limit
     *
     * @return array
     */
    public static function select(string $table, ?array $columns = ['*'], ?array $join = null, ?array $where = null, ?array $group = null, ?array $order = ['id' => 'DESC'], ?array $limit = []): array
    {
        $data = [];

        // $sql = "SELECT * FROM ".$table." ORDER BY  ASC";
        $sql = "SELECT ".self::columns($columns)." FROM ".self::key($table).self::join($join).self::where($where).self::group($group).self::order($order).self::limit($limit);

        // check db for lat lng
        $result = self::query($sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = (object) $row;
        }

        self::$lastTotal = empty($limit) ? count($data) : self::count($table, $where);

        return $data;
    }

    /**
     * Get Last Query
     *
     * @return string
     */
    public static function getLastQuery(): string
    {
        return strval(self::$lastQuery);
    }

    /**
     * Get Last Total Amount
     *
     * @return int
     */
    public static function getLastTotal(): int
    {
        return intval(self::$lastTotal);
    }


    /**
     * Select Count
     *
     * @param string $table
     * @param array  $where
     *
     * @return int|null
     */
    public static function count(string $table, array $where = []): ?int
    {
        $data = [];

        // $sql = "SELECT * FROM ".$table." ORDER BY  ASC";
        $sql = "SELECT COUNT(*) FROM ".self::key($table).self::where($where);

        // check db for lat lng
        $result = self::query($sql);
        $data = mysqli_fetch_column($result);

        return !is_numeric($data) ? null : intval($data);
    }

    /**
     * Select Sum
     *
     * @param string $table
     * @param string $column
     * @param array  $where
     *
     * @return float|null
     */
    public static function sum(string $table, string $column, array $where = []): ?float
    {
        $data = [];

        // $sql = "SELECT * FROM ".$table." ORDER BY  ASC";
        $sql = "SELECT SUM(".self::key($column).") FROM ".self::key($table).self::where($where);

        // check db for lat lng
        $result = self::query($sql);
        $data = mysqli_fetch_column($result);

        return is_numeric($data) ? floatval($data) : null;
    }


    /**
     * Delete all Entries
     *
     * @param string $table
     * @param array  $where
     *
     * @return bool
     */
    public static function delete(string $table, array $where): bool
    {
        $sql = "DELETE FROM ".self::key($table).self::where($where);

        return self::query($sql) ? true : false;
    }

    /**
     * Insert Data
     *
     * @param string       $table
     * @param object|array $data
     *
     * @return bool
     */
    public static function insert(string $table, object|array $data): bool
    {
        $data = (array) $data;

        $values = [];
        $keys = [];
        foreach ($data as $key => $value) {
            $values[] = self::value($value);
            $keys[] = self::key($key);
        }

        $sql = "INSERT INTO ".self::key($table)." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";

        return self::query($sql) ? true : false;
    }

    /**
     * Update Data
     *
     * @param string       $table
     * @param object|array $data
     * @param array        $where
     *
     * @return bool
     */
    public static function update(string $table, object|array $data, array $where): bool
    {
        $sets = [];
        foreach ((array) $data as $key => $value) {
            $sets[] = self::key($key).' = '.self::value($value);
        }

        $sql = "UPDATE ".self::key($table)." SET ".implode(', ', $sets).self::where($where);

        return self::query($sql) ? true : false;
    }

    /**
     * Build Columns
     *
     * @param array|null $columns
     *
     * @return string
     */
    public static function columns(?array $columns): string
    {
        $columnsArray = [];

        if (empty($columns)) {
            $columns = ['*'];
        }

        foreach ($columns as $value) {
            $columnKey = self::key($value);
            if (preg_match('/(SUM|COUNT|MAX|MIN)\(([a-z0-9\_\.]+)\)\ (as)\ ([a-z0-9\_]+)$/i', $value, $matches)) {
                $columnKey = self::key($matches[1]).'('.$matches[2].') '.$matches[3]." '".self::key($matches[4])."'";
            } elseif (preg_match('/([a-z0-9\_\.]+)\ (as) ([a-z0-9\_\.]+)/i', $value, $matches)) {
                $columnKey = self::key($matches[1]).' '.$matches[2]." '".self::key($matches[3])."'";
            }
            $columnsArray[] = $value !== '*' ? $columnKey : '*';
        }

        if (!empty($columnsArray)) {
            return implode(', ', $columnsArray);
        }

        return '*';
    }

    /**
     * Build Where
     *
     * @param array|null $where
     * @param string     $condition
     * @param bool       $addLabel
     *
     * @return string
     */
    public static function where(?array $where, string $condition = 'AND', bool $addLabel = true): string
    {
        if (!empty($where)) {
            $wheres = [];
            foreach ($where as $key => $value) {
                if (in_array($key, ['OR', 'AND'], true)) {
                    $wheres[] = '( '.self::where($value, $key, false).' )';
                    continue;
                }
                $whereKey = self::key($key);
                $compare = '=';
                preg_match('/\[(.*)\]/', $key, $compareMatch);
                if (!empty($compareMatch[1]) && in_array($compareMatch[1], ['!', '>', '>=', '<', '<=', '~'], true)) {
                    $compare = $compareMatch[1];
                    if ($compare === '!') {
                        $compare = '!=';
                    }
                    if ($compare === '~') {
                        $compare = 'LIKE';
                    }
                }
                if (is_array($value)) {
                    $ors = [];
                    foreach ($value as $valueValue) {
                        if (!is_string($valueValue) && is_int($valueValue) && is_float($valueValue)) {
                            throw new Exception('Database Error: Inner value must be one of String, Int, or Float', 1);
                        }
                        $ors[] = $whereKey.' '.$compare.' '.self::value($valueValue, $compare === 'LIKE');
                    }
                    if (!empty($ors)) {
                        $wheres[] = '( '.implode(' OR ', $ors).' )';
                    }
                } else {
                    $wheres[] = $whereKey.' '.$compare.' '.self::value($value, $compare === 'LIKE');
                }
            }

            if (!empty($wheres)) {
                return ($addLabel ? ' WHERE ' : '').implode(' '.($condition === 'OR' ? 'OR' : 'AND').' ', $wheres);
            }
        }

        return '';
    }

    /**
     * Build Join
     *
     * @param array|null $join
     *
     * @return string
     */
    public static function join(?array $join): string
    {
        if (!empty($join)) {
            $joins = [];

            foreach ($join as $key => $value) {
                if (is_array($value)) {
                    $joinKey = self::key($key);
                    $inners = [];
                    foreach ($value as $innerKey => $innerValue) {
                        $inners[] = self::key($innerKey).'='.$innerValue;
                    }

                    $joins[] = 'INNER JOIN '.$joinKey.' ON '.implode(' AND ', $inners);
                }
            }

            if (!empty($joins)) {
                return ' '.implode(' ', $joins);
            }
        }

        return '';
    }

    /**
     * Build Group By
     *
     * @param array|null $group
     *
     * @return string
     */
    public static function group(?array $group): string
    {
        if (!empty($group)) {
            $groups = [];

            foreach ($group as $value) {
                $groupValue = self::key($value);
                $groups[] = $groupValue;
            }

            if (!empty($groups)) {
                return ' GROUP BY '.implode(', ', $groups);
            }
        }

        return '';
    }

    /**
     * Build Order
     *
     * @param array|null $order
     *
     * @return string
     */
    public static function order(?array $order): string
    {
        if (!empty($order)) {
            $orders = [];

            foreach ($order as $key => $value) {
                $orderKey = self::key($key);
                $orderValue = $value === 'ASC' ? 'ASC' : 'DESC';
                $orders[] = $orderKey.' '.$orderValue;
            }

            if (!empty($orders)) {
                return ' ORDER BY '.implode(', ', $orders);
            }
        }

        return '';
    }

    /**
     * Build Limit
     *
     * @param array|int|null $limit
     *
     * @return string
     */
    public static function limit(array|int|null $limit): string
    {
        if (!empty($limit)) {
            $limits = [];
            if (isset($limit[0]) && is_numeric($limit[0])) {
                $limits[] = intval($limit[0]);

                if (isset($limit[1]) && is_numeric($limit[1])) {
                    $limits[] = intval($limit[1]);
                }

                return ' LIMIT '.implode(', ', $limits);
            }
        }

        return '';
    }

    /**
     * Check and Update Tables
     *
     * @param string $schemaFile - Path to Schema File. File must return a DB Scheme Array.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function updateSchema(string $schemaFile)
    {
        if (empty($schemaFile) || !file_exists($schemaFile)) {
            Alerts::addAlert('error', 'Missing DB Schema File');
            throw new Exception(sprintf("SpryPHP: DB Schema File Not Found: %s)", $schemaFile), 1);
        }

        $schema = require $schemaFile;

        if (empty($schema)) {
            throw new Exception(sprintf("SpryPHP: DB Schema File Not Formatted Correctly: %s)", $schemaFile), 1);
        }

        foreach ($schema as $table => $columns) {
            if (!in_array($table, self::tables(), true)) {
                $sql = 'CREATE TABLE '.self::key($table).' (id VARCHAR(36) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id))';
                if (!self::query($sql)) {
                    throw new Exception(sprintf('Adding Table (%s) failed: %s SQL: %s', $table, mysqli_connect_error(), $sql), 1);
                }
            }

            $existingColumns = [];
            $result = self::query('SHOW COLUMNS FROM '.self::key($table));
            if ($result) {
                while ($row = mysqli_fetch_array($result)) {
                    $existingColumns[] = (object) [
                        'name' => $row['Field'],
                        'type' => strtoupper($row['Type']),
                        'null' => !empty($row['Null']) && strtolower($row['Null']) === 'yes' ? true : false,
                        'default' => $row['Default'],
                        'index' => !empty($row['Key']) && in_array($row['Key'], ['MUL', 'PRI'], true) ? true : (!empty($row['Key']) && $row['Key'] === 'UNI' ? 'unique' : false),
                    ];
                }
            }

            $after = 'id';
            foreach ($columns as $column) {
                $column = (object) $column;
                if (!in_array($column->name, array_values(array_column($existingColumns, 'name')), true)) {
                    $sql = 'ALTER TABLE '.self::key($table).' ADD '.self::key($column->name).' '.$column->type.' '.($column->null ? 'NULL' : 'NOT NULL').' DEFAULT '.(is_null($column->default) ? 'NULL' : (in_array($column->default, ['CURRENT_TIMESTAMP', 'NOW()'], true) || (is_int($column->default) || is_float($column->default)) ? $column->default : '"'.$column->default.'"')).' AFTER '.$after;
                    if (!self::query($sql)) {
                        throw new Exception(sprintf('Adding Column (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql), 1);
                    }
                    $existingColumns[] = (object) [
                        'name' => $column->name,
                        'type' => $column->type,
                        'null' => $column->null,
                        'default' => $column->default,
                        'index' => $column->index,
                    ];
                }
                $after = self::key($column->name);
                foreach ($existingColumns as $existingColumn) {
                    if (!in_array($existingColumn->name, ['id', 'created_at', 'updated_at'], true) && !in_array($existingColumn->name, array_values(array_column($columns, 'name')), true)) {
                        $sql = 'ALTER TABLE '.self::key($table).' DROP COLUMN '.self::key($existingColumn->name);
                        if (!self::query($sql)) {
                            throw new Exception(sprintf('Dropping Column (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql), 1);
                        }
                    } elseif ($column->name === $existingColumn->name) {
                        if ((!empty($column->type) && !empty($existingColumn->type) && trim(strtolower($column->type)) !== trim(strtolower($existingColumn->type))) || (is_null($column->default) && !is_null($existingColumn->default)) || (!is_null($column->default) && is_null($existingColumn->default)) || (!empty($column->default) && !empty($existingColumn->default) && trim(strtolower($column->default)) !== trim(strtolower($existingColumn->default))) || $column->null !== $existingColumn->null) {
                            $sql = 'ALTER TABLE '.self::key($table).' MODIFY '.self::key($column->name).' '.strtoupper($column->type).' '.($column->null ? 'NULL' : 'NOT NULL').' DEFAULT '.(is_null($column->default) ? 'NULL' : (in_array($column->default, ['CURRENT_TIMESTAMP', 'NOW()'], true) || (is_int($column->default) || is_float($column->default)) ? $column->default : '"'.$column->default.'"'));
                            if (!self::query($sql)) {
                                throw new Exception(sprintf('Updating Column (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql), 1);
                            }
                        }
                        if (!in_array($existingColumn->name, ['id', 'created_at', 'updated_at'], true) && isset($column->index) && isset($existingColumn->index) && $column->index !== $existingColumn->index) {
                            if ($existingColumn->index) {
                                $sql = 'ALTER TABLE '.self::key($table).' DROP INDEX index_'.self::key($column->name);
                                if (!self::query($sql)) {
                                    throw new Exception(sprintf('Dropping Index (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql), 1);
                                }
                            }
                            if ($column->index) {
                                $sql = 'ALTER TABLE '.self::key($table).' ADD '.($column->index === 'unique' ? 'UNIQUE' : 'INDEX').' index_'.self::key($column->name).' ('.self::key($column->name).')';
                                if (!self::query($sql)) {
                                    throw new Exception(sprintf('Adding Index (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql), 1);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Build Key
     *
     * @param string $key
     *
     * @return string
     */
    private static function key(string $key): string
    {
        $key = trim(preg_replace('/[^a-zA-Z0-9\_\.]/', '', strval($key)));

        return strval($key);
    }

    /**
     * Build Value
     *
     * @param string|int|float $value
     * @param bool             $isLike
     *
     * @return string|int|float
     */
    private static function value(string|int|float $value, bool $isLike = false): string|int|float
    {
        if (!self::$db) {
            self::connect();
        }

        if ($isLike) {
            $value = "'%".mysqli_real_escape_string(self::$db, (string) strval($value))."%'";
        } elseif (!in_array($value, ['NOW()', 'NULL'], true)) {
            if (!is_int($value) && !is_float($value)) {
                $value = "'".mysqli_real_escape_string(self::$db, (string) strval($value))."'";
            }
        }

        return $value;
    }
}
