<?php declare(strict_types=1);
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
     * @var \mysqli|false $db
     */
    private static \mysqli|false $db = false;

    /**
     * The Last Query
     *
     * @var string $lastQuery
     */
    private static string $lastQuery = '';

    /**
     * The Last Error
     *
     * @var string $lastError
     */
    private static string $lastError = '';

    /**
     * The Last Total Count
     *
     * @var int|null $lastTotal
     */
    private static ?int $lastTotal = null;

    /**
     * Initiate the DB
     *
     * @throws Exception
     *
     * @return void
     */
    public static function connect(): void
    {
        // MySQL Variables
        $hostname = getenv('DB_HOST');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $database = getenv('DB_NAME');
        $socket   = null;

        // Check if Variables are set
        if (!$hostname) {
            throw new Exception('SpryPhp: DB Connection Error. Environment Variable (DB_HOST) is not set.');
        }

        if (!$username) {
            throw new Exception('SpryPhp: DB Connection Error. Environment Variable (DB_USER) is not set.');
        }

        if (!$password) {
            throw new Exception('SpryPhp: DB Connection Error. Environment Variable (DB_PASS) is not set.');
        }

        if (!$database) {
            throw new Exception('SpryPhp: DB Connection Error. Environment Variable (DB_NAME) is not set.');
        }

        if (strpos($hostname, ':')) {
            $serverInfo = explode(':', $hostname);
            $hostname = $serverInfo[0];
            $socket = $serverInfo[1];
        }

        // Connect to MySQL - Check if connection is Successful or not
        if (!(self::$db = mysqli_connect($hostname, $username, $password, null, null, $socket))) {
            throw new Exception(sprintf('SpryPhp: Server Connection failed: %s', mysqli_connect_error()));
        }

        // Convert Int and Floats back to PHP.
        mysqli_options(self::$db, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

        if (!mysqli_select_db(self::$db, $database)) {
            throw new Exception(sprintf('SpryPhp: Database Connection failed: %s', mysqli_connect_error()));
        }
    }

    /**
     * Get Tables
     *
     * @return array<string>
     */
    public static function tables(): array
    {
        $tables = [];

        $result = self::query('SHOW TABLES');
        if ($result && !is_bool($result)) { // mysqli_fetch_assoc requires mysql_result not true.
            while ($row = mysqli_fetch_row($result)) {
                if (!empty($row[0]) && is_string($row[0])) {
                    $tables[] = $row[0];
                }
            }
        }

        return $tables;
    }

    /**
     * Query Db
     *
     * @param string $sql
     *
     * @return \mysqli_result|true|null
     */
    public static function query(string $sql): \mysqli_result|true|null
    {
        // Make sure we are connected.
        if (!self::$db) {
            self::connect();
        }

        if (!self::$db) {
            throw new Exception('SpryPhp: Database Error: Could not Connect to Database');
        }

        self::$lastError = '';

        try {
            self::$lastQuery = $sql;
            $result = mysqli_query(self::$db, $sql);
            if ($result) {
                return $result;
            }

            $error = mysqli_error(self::$db);
            if ($error) {
                self::$lastError = sprintf('SpryPhp: Database Error: %s', $error);
                throw new Exception(sprintf('SpryPhp: Database Error: %s', $error));
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('SpryPhp: Database Error: %s', $e->getMessage()));
        }

        return null;
    }

    /**
     * Get Single Item
     *
     * @param string                                         $table
     * @param array<string>|null                             $columns
     * @param array<string,string|array<string,string>>|null $where
     * @param array<string,string>|null                      $order
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
     * @param string                                         $table
     * @param string[]|null                                  $columns
     * @param array<string,string>|null                      $join
     * @param array<string,string|array<string,string>>|null $where
     * @param array<string,string>|null                      $group
     * @param array<string,string>|null                      $order
     * @param array<int,string|int>|int|null                 $limit
     *
     * @return array<int,object>
     */
    public static function select(string $table, ?array $columns = ['*'], ?array $join = null, ?array $where = null, ?array $group = null, ?array $order = ['id' => 'DESC'], array|int|null $limit = []): array
    {
        $data = [];

        $sql = "SELECT ".self::columns($columns)." FROM ".self::key($table).self::join($join).self::where($where).self::group($group).self::order($order).self::limit($limit);

        // check db for lat lng
        $result = self::query($sql);
        if ($result && !is_bool($result)) { // mysqli_fetch_assoc requires mysql_result not true.
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = (object) $row;
            }
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
     * Get Last Error
     *
     * @return string
     */
    public static function getLastError(): string
    {
        return strval(self::$lastError);
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
     * @param string                                         $table
     * @param array<string,string|array<string,string>>|null $where
     *
     * @return int|null
     */
    public static function count(string $table, ?array $where = []): ?int
    {
        $data = [];

        $sql = "SELECT COUNT(*) FROM ".self::key($table).self::where($where);

        // check db for lat lng
        $result = self::query($sql);
        if ($result && !is_bool($result)) { // mysqli_fetch_column requires mysql_result not true.
            $data = mysqli_fetch_column($result);
        }

        return !is_numeric($data) ? null : intval($data);
    }

    /**
     * See if table has at least one
     *
     * @param string                                         $table
     * @param array<string,string|array<string,string>>|null $where
     *
     * @return bool|null
     */
    public static function has(string $table, ?array $where = []): ?bool
    {
        $result = self::count($table, $where);

        return is_null($result) ? null : !empty($result);
    }

    /**
     * Select Sum
     *
     * @param string                                    $table
     * @param string                                    $column
     * @param array<string,string|array<string,string>> $where
     *
     * @return float|null
     */
    public static function sum(string $table, string $column, array $where = []): ?float
    {
        $data = [];

        $sql = "SELECT SUM(".self::key($column).") FROM ".self::key($table).self::where($where);

        // check db for lat lng
        $result = self::query($sql);
        if ($result && !is_bool($result)) { // mysqli_fetch_column requires mysql_result not true.
            $data = mysqli_fetch_column($result);
        }

        return is_numeric($data) ? floatval($data) : null;
    }


    /**
     * Delete entries by WHERE Array
     *
     * @param string                                    $table
     * @param array<string,string|array<string,string>> $where Where Array
     *
     * @return bool
     */
    public static function delete(string $table, array $where): bool
    {
        $sql = "DELETE FROM ".self::key($table).self::where($where);

        return self::query($sql) ? true : false;
    }

    /**
     * Delete ALL Entries from a Table
     *
     * @param string $table
     *
     * @return bool
     */
    public static function truncate(string $table): bool
    {
        $sql = "TRUNCATE TABLE ".self::key($table);

        return self::query($sql) ? true : false;
    }

    /**
     * Insert Data
     *
     * @param string                                   $table
     * @param \stdClass|array<string,string|int|float> $data
     *
     * @return bool
     */
    public static function insert(string $table, object|array $data): bool
    {
        $data = (object) $data;

        $values = [];
        $keys = [];

        // If no ID is passed then lets add one.
        if (!isset($data->id)) {
            $data->id = Functions::newUuid();
        }

        foreach ((array) $data as $key => $value) {
            if (!is_scalar($value) || is_bool($value)) {
                throw new Exception('Database Error: Inner value must be one of String, Int, or Float');
            }
            $values[] = self::value($value);
            $keys[] = self::key($key);
        }

        $sql = "INSERT INTO ".self::key($table)." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";

        return self::query($sql) ? true : false;
    }

    /**
     * Update Data
     *
     * @param string                                    $table
     * @param object|array<string,string|int|float>     $data
     * @param array<string,string|array<string,string>> $where
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function update(string $table, object|array $data, array $where): bool
    {
        $sets = [];
        foreach ((array) $data as $key => $value) {
            if (!is_scalar($value) || is_bool($value)) {
                throw new Exception('Database Error: Inner value must be one of String, Int, or Float');
            }
            $sets[] = self::key($key).' = '.self::value($value);
        }

        $sql = "UPDATE ".self::key($table)." SET ".implode(', ', $sets).self::where($where);

        return self::query($sql) ? true : false;
    }

    /**
     * Build Columns
     *
     * @param string[]|null $columns
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

        return implode(', ', $columnsArray);
    }

    /**
     * Build Where
     *
     * @param array<string,string|array<string,string>>|null $where
     * @param string                                         $condition
     * @param bool                                           $addLabel
     *
     * @return string
     */
    public static function where(?array $where, string $condition = 'AND', bool $addLabel = true): string
    {
        if (!empty($where)) {
            $wheres = [];
            foreach ($where as $key => $value) {
                if (in_array($key, ['OR', 'AND'], true)) {
                    $wheres[] = '( '.self::where($value, $key, false).' )'; // @phpstan-ignore argument.type
                    continue;
                }
                $whereKey = self::key($key);
                $compare = '=';
                preg_match('/\[(.*)\]$/', trim($key), $compareMatch);
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
                        if (!is_string($valueValue) && !is_int($valueValue) && !is_float($valueValue)) {
                            throw new Exception('Database Error: Inner value must be one of String, Int, or Float');
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

            if ($wheres) {
                return ($addLabel ? ' WHERE ' : '').implode(' '.($condition === 'OR' ? 'OR' : 'AND').' ', $wheres);
            }
        }

        return '';
    }

    /**
     * Build Join
     *
     * @param array<string,string|array<string,string>>|null $join
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

            if ($joins) {
                return ' '.implode(' ', $joins);
            }
        }

        return '';
    }

    /**
     * Build Group By
     *
     * @param array<string>|null $group
     *
     * @return string
     */
    public static function group(?array $group): string
    {
        $groups = [];

        if ($group) {
            foreach ($group as $value) {
                $groupValue = self::key($value);
                $groups[] = $groupValue;
            }

            return ' GROUP BY '.implode(', ', $groups);
        }

        return '';
    }

    /**
     * Build Order
     *
     * @param array<string,string>|null $order
     *
     * @return string
     */
    public static function order(?array $order): string
    {
        $orders = [];
        if ($order) {
            foreach ($order as $key => $value) {
                $orderKey = self::key($key);
                $orderValue = $value === 'ASC' ? 'ASC' : 'DESC';
                $orders[] = $orderKey.' '.$orderValue;
            }

            return ' ORDER BY '.implode(', ', $orders);
        }

        return '';
    }

    /**
     * Build Limit
     *
     * @param array<int,string|int>|int|null $limit
     *
     * @return string
     */
    public static function limit(array|int|null $limit): string
    {
        if (!empty($limit)) {
            $limits = [];
            if (is_int($limit)) {
                $limits[] = intval($limit);
            }
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
     * @param string $schemaFile       Path to Schema File. File must return a DB Scheme Array.
     * @param bool   $forceDestructive Allows for Destructive calls like drops or index changes, etc.
     *
     * @throws Exception
     *
     * @return string[]
     */
    public static function updateSchema(string $schemaFile, bool $forceDestructive = false): array
    {
        $changes = [];

        if (empty($schemaFile) || !file_exists($schemaFile)) {
            throw new Exception(sprintf("SpryPHP: DB Schema File Not Found: (%s)", $schemaFile));
        }

        $schema = require $schemaFile;

        if (empty($schema) || !is_array($schema)) {
            throw new Exception(sprintf("SpryPHP: DB Schema File is Empty or Not Formatted Correctly: (%s)", $schemaFile));
        }

        foreach ($schema as $table => $columns) {
            if (empty($table) || !is_string($table)) {
                throw new Exception(sprintf("SpryPHP: DB Schema File is Not Formatted Correctly: (%s) Invalid Table Name.", $schemaFile));
            }

            if (empty($columns) || !is_array($columns)) {
                throw new Exception(sprintf("SpryPHP: DB Schema File is Not Formatted Correctly: (%s) Invalid Columns for Table (%s).", $schemaFile, $table));
            }

            if (!in_array($table, self::tables(), true)) {
                $sql = 'CREATE TABLE '.self::key($table).' (id VARCHAR(36) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id))';
                if (!self::query($sql)) {
                    throw new Exception(sprintf('SpryPhp: Adding Table (%s) failed: %s SQL: %s', $table, mysqli_connect_error(), $sql));
                }
                $changes[] = 'Added Table ['.self::key($table).']';
            }

            $existingColumns = [];
            $result = self::query('SHOW COLUMNS FROM '.self::key($table));
            if ($result && !is_bool($result)) {
                while ($row = mysqli_fetch_array($result)) {
                    if (!empty($row['Field']) && is_string($row['Field'])) {
                        $existingColumns[] = self::buildColumn($row['Field'], (object) [
                            'type' => $row['Type'],
                            'null' => !empty($row['Null']) && is_string($row['Null']) && strtolower($row['Null']) === 'yes' ? true : false,
                            'default' => $row['Default'],
                            'index' => !empty($row['Key']) && in_array($row['Key'], ['MUL', 'PRI'], true) ? true : (!empty($row['Key']) && $row['Key'] === 'UNI' ? 'unique' : false),
                        ]);
                    }
                }
            }

            foreach ($existingColumns as $existingColumn) {
                if (!in_array($existingColumn->name, ['id', 'created_at', 'updated_at'], true) && !in_array($existingColumn->name, array_keys($columns), true)) {
                    $sql = 'ALTER TABLE '.self::key($table).' DROP COLUMN '.self::key($existingColumn->name);
                    if (!$forceDestructive) {
                        $changes[] = '* NOT PERFORMED (Needs Force Destructive): Altered Table ['.self::key($table).'] Dropped Column ['.self::key($existingColumn->name).']';
                    } else {
                        if (!self::query($sql)) {
                            throw new Exception(sprintf('SpryPhp: Dropping Column (%s) failed: %s SQL: %s', $existingColumn->name, mysqli_connect_error(), $sql));
                        }
                        $changes[] = 'Altered Table ['.self::key($table).'] Dropped Column ['.self::key($existingColumn->name).']';
                    }
                }
            }

            $after = 'id';
            foreach ($columns as $columnName => $column) {
                $column = self::buildColumn($columnName, (object) $column);
                if (!in_array($column->name, array_column($existingColumns, 'name'), true)) {
                    $sql = 'ALTER TABLE '.self::key($table).' ADD '.self::key($column->name).' '.$column->type.' '.($column->null ? 'NULL' : 'NOT NULL').' DEFAULT '.(is_null($column->default) ? 'NULL' : (in_array($column->default, ['CURRENT_TIMESTAMP', 'NOW()'], true) || is_int($column->default) || is_float($column->default) ? $column->default : '"'.$column->default.'"')).' AFTER '.$after;
                    if ($column->index) {
                        $sql .= ', '.self::getAddIndex($column);
                    }
                    if (!self::query($sql)) {
                        throw new Exception(sprintf('SpryPhp: Adding Column (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql));
                    }
                    $changes[] = 'Altered Table ['.self::key($table).'] Added Column ['.self::key($column->name).']'.($column->index ? ' With Index (index_'.self::key($column->name).')' : '');
                    $existingColumns[] = self::buildColumn($columnName, (object) $column);
                }
                $after = self::key($column->name);

                foreach ($existingColumns as $existingColumn) {
                    if ($column->name === $existingColumn->name) {
                        if ((!empty($column->type) && !empty($existingColumn->type) && trim(strtolower($column->type)) !== trim(strtolower($existingColumn->type))) || (is_null($column->default) && !is_null($existingColumn->default)) || (!is_null($column->default) && is_null($existingColumn->default)) || (!empty($column->default) && !empty($existingColumn->default) && ((is_string($column->default) && trim(strtolower(strval($column->default))) !== trim(strtolower(strval($existingColumn->default)))) || (!is_string($column->default) && $column->default !== $existingColumn->default))) || $column->null !== $existingColumn->null) {
                            $sql = 'ALTER TABLE '.self::key($table).' MODIFY '.self::key($column->name).' '.strtoupper($column->type).' '.($column->null ? 'NULL' : 'NOT NULL').' DEFAULT '.(is_null($column->default) ? 'NULL' : (in_array($column->default, ['CURRENT_TIMESTAMP', 'NOW()'], true) || is_int($column->default) || is_float($column->default) ? $column->default : '"'.$column->default.'"'));
                            if (!$forceDestructive) {
                                $changes[] = '* NOT PERFORMED (Needs Force Destructive): Altered Table ['.self::key($table).'] Modified Column ['.self::key($column->name).']';
                            } else {
                                if (!self::query($sql)) {
                                    throw new Exception(sprintf('SpryPhp: Updating Column (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql));
                                }
                                $changes[] = 'Altered Table ['.self::key($table).'] Modified Column ['.self::key($column->name).']';
                            }
                        }
                        if (!in_array($existingColumn->name, ['id', 'created_at', 'updated_at'], true) && isset($column->index) && isset($existingColumn->index) && $column->index !== $existingColumn->index) {
                            if ($existingColumn->index) {
                                $sql = 'ALTER TABLE '.self::key($table).' DROP INDEX index_'.self::key($column->name);
                                if (!$forceDestructive) {
                                    $changes[] = '* NOT PERFORMED (Needs Force Destructive): Altered Table ['.self::key($table).'] Dropped Index [index_'.self::key($column->name).']';
                                } else {
                                    if (!self::query($sql)) {
                                        throw new Exception(sprintf('SpryPhp: Dropping Index (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql));
                                    }
                                    $changes[] = 'Altered Table ['.self::key($table).'] Dropped Index [index_'.self::key($column->name).']';
                                }
                            }
                            if ($column->index) {
                                $sql = 'ALTER TABLE '.self::key($table).' '.self::getAddIndex($column);
                                if (!self::query($sql)) {
                                    throw new Exception(sprintf('SpryPhp: Adding Index (%s) failed: %s SQL: %s', $column->name, mysqli_connect_error(), $sql));
                                }
                                $changes[] = 'Altered Table ['.self::key($table).'] Added Index [index_'.self::key($column->name).']';
                            }
                        }
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Build Column
     *
     * @param string $columnName
     * @param object $column
     *
     * @throws Exception
     *
     * @return object{name:string,type:string,default:string|int|float|null,null:boolean,index:string|array<string,string>|null}
     */
    private static function buildColumn(string $columnName, object $column): object
    {
        if (!trim($columnName) || empty($columnName)) {
            throw new Exception('SpryPhp: Column `name` is required in Schema File and must be a string.');
        }

        return (object) [
            'name'    => trim($columnName),
            'type'    => isset($column->type) && is_string($column->type) && trim(strtolower($column->type)) !== 'string' ? trim($column->type) : 'VARCHAR(128)',
            'default' => isset($column->default) ? (is_string($column->default) ? trim($column->default) : (is_scalar($column->default) && !is_bool($column->default) ? $column->default : null)) : null,
            'null'    => isset($column->null) ? boolval($column->null) : true,
            'index'   => isset($column->index) ? (is_string($column->index) ? strtolower(trim($column->index)) : (is_scalar($column->index) && is_array($column->index) ? $column->index : null)) : null,
        ];
    }

    /**
     * Build Add Index
     *
     * @param object{name:string,type:string,default:string|int|float|null,null:boolean,index:string|array<string,string>|null} $column
     *
     * @return string
     */
    private static function getAddIndex(object $column): string
    {
        return 'ADD '.($column->index === 'unique' ? 'UNIQUE' : 'INDEX').' index_'.self::key($column->name).' ('.self::key($column->name).')';
    }

    /**
     * Build Key
     *
     * @param string $key
     *
     * @throws Exception
     *
     * @return string
     */
    private static function key(string $key): string
    {
        if (empty($key)) {
            throw new Exception('SpryPhp: Key value is Empty.');
        }

        $stringValue = preg_replace('/[^a-zA-Z0-9\_\.]/', '', $key);

        return $stringValue ? trim($stringValue) : '';
    }

    /**
     * Build Value
     *
     * @param string|int|float $value
     * @param bool             $isLike
     *
     * @throws Exception
     *
     * @return string|int|float
     */
    private static function value(string|int|float $value, bool $isLike = false): string|int|float
    {
        if (!self::$db) {
            self::connect();
        }

        if (!self::$db) {
            throw new Exception(sprintf('SpryPhp: Server Connection failed: %s', mysqli_connect_error()));
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
