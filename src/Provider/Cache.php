<?php declare(strict_types=1);
/**
 * This file is to handle the Cache
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Functions;
use SpryPhp\Type\TypeStorage;
use SpryPhp\Model\CacheModel;
use Exception;

/**
 * Class for Cache
 */
class Cache
{
    /**
     * Return a Value from the Cache by key
     *
     * @param string $key Name of the Value to retrieve.
     *
     * @throws Exception
     *
     * @return mixed Returns Mixed Value on Success. Null on Failed. Success might be NULL as well.
     */
    public static function get(string $key): mixed
    {
        $key = Functions::sanitizeString($key);

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when getting a Cache value.');
        }

        $cache = self::getCache($key);

        if (!$cache) {
            return null;
        }

        if ($cache->isExpired()) {
            self::delete($key);

            return null;
        }

        return $cache->value;
    }

    /**
     * Set a Cache value by key
     *
     * @param string $key     Name of the Cache Key to set.
     * @param mixed  $value   Value to set.
     * @param int    $expires Time to Expire in Seconds. 0 = Never Expire.
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function set(string $key, mixed $value, int $expires = 0): bool
    {
        $key = Functions::sanitizeString($key);

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when setting a Cache value.');
        }

        $cache = new CacheModel((object) [
            'key'     => $key,
            'value'   => $value,
            'expires' => $expires ? (time() + $expires) : 0,
        ]);

        if (!self::setData($cache)) {
            throw new Exception('SpryPHP: Error Setting Cache. Check to make sure you have the proper Storage configuration.');
        }

        return true;
    }

    /**
     * Delete a Cache value by key
     *
     * @param string $key Name of the Cache to Delete.
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function delete(string $key): bool
    {
        $key = Functions::sanitizeString($key);

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when deleting from Cache.');
        }

        return self::deleteData($key);
    }

    /**
     * Extend the Cache Expires value by key
     *
     * @param string $key     Name of the Cache to Delete.
     * @param int    $expires Time to Expire in Seconds. 0 = Never Expire.
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function extend(string $key, int $expires = 0): bool
    {
        $key = Functions::sanitizeString($key);

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when deleting from Cache.');
        }

        $cache = self::getCache($key);

        if (!$cache) {
            throw new Exception('SpryPHP: Error extending Cache. Cache not found.');
        }

        $cache->expires = $expires ? (time() + $expires) : 0;

        return self::setData($cache);
    }

    /**
     * Check if Cache exists
     *
     * @param string $key Name of the Cache to check for.
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function exists(string $key): bool
    {
        $key = Functions::sanitizeString($key);

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when deleting from Cache.');
        }

        $cache = self::getCache($key);

        if ($cache) {
            return true;
        }

        return false;
    }

    /**
     * Clear Expired
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function clearExpired(): bool
    {
        $driver = Functions::constantString('APP_CACHE_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: Cache Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_CACHE_FILE_PATH');

            if (!is_dir($path)) {
                return true;
            }

            $files = glob($path.'/*');

            if ($files === false) {
                throw new Exception(sprintf('SpryPHP Error: Cache Getting Files Error on path (%s/*).', $path));
            }

            foreach ($files as $file) {
                $contents = file_get_contents($file);
                if ($contents) {
                    $cache = new CacheModel($contents);
                    if ($cache->isExpired()) {
                        unlink($file);
                    }
                }
            }
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            Db::delete(
                table: Functions::constantString('APP_CACHE_DB_TABLE'),
                where: ['expires[<]' => time(), 'expires[>]' => 0] // @phpstan-ignore argument.type
            );
        }

        return true;
    }

    /**
     * Clear Expired
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function clearAll(): bool
    {
        $driver = Functions::constantString('APP_CACHE_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: Cache Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_CACHE_FILE_PATH');

            if (!is_dir($path)) {
                return true;
            }

            $files = glob($path.'/*');

            if ($files === false) {
                throw new Exception(sprintf('SpryPHP Error: Cache Getting Files Error on path (%s/*).', $path));
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            Db::truncate(Functions::constantString('APP_CACHE_DB_TABLE'));
        }

        return true;
    }

    /**
     * Get Cache Data
     *
     * @param string $key Name of the Value to retrieve.
     *
     * @throws Exception
     *
     * @return CacheModel|null
     */
    private static function getCache(string $key): ?CacheModel
    {
        $driver = Functions::constantString('APP_CACHE_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: Cache Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_CACHE_FILE_PATH');

            if (!is_dir($path)) {
                return null;
            }

            $file = $path.'/'.$key;

            if (file_exists($file)) {
                $contents = file_get_contents($file);
                if ($contents) {
                    return new CacheModel($contents);
                }
            }
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            $result = Db::get(
                table: Functions::constantString('APP_CACHE_DB_TABLE'),
                columns: ['name', 'value', 'expires'],
                where: [
                    'name' => $key,
                ]
            );

            if (isset($result->value) && is_string($result->value)) {
                $result->value = json_decode($result->value);
            }

            return new CacheModel($result ? $result : null);
        }

        return null;
    }

    /**
     * Set Cache Data
     *
     * @param CacheModel $cache
     *
     * @throws Exception
     *
     * @return bool
     */
    private static function setData(CacheModel $cache): bool
    {
        $driver = Functions::constantString('APP_CACHE_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: Cache Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_CACHE_FILE_PATH');

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            if (!is_dir($path)) {
                throw new Exception(sprintf('SpryPHP Error: Cache File Path does not exist and could not be created (%s).', $path));
            }

            $cacheString = json_encode($cache);

            if (!$cacheString) {
                throw new Exception(sprintf('SpryPHP Error: Cache File Path does not exist and could not be created (%s).', $path));
            }

            $file = $path.'/'.$cache->key;
            $saved = file_put_contents($file, $cacheString);

            return $saved === false ? false : true;
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            $value = !empty($cache->value) ? json_encode($cache->value) : '';
            if (Db::has(Functions::constantString('APP_CACHE_DB_TABLE'), ['name' => $cache->key])) {
                if (Db::update(Functions::constantString('APP_CACHE_DB_TABLE'), ['value' => $value ? $value : '', 'expires' => $cache->expires], ['name' => $cache->key])) {
                    return true;
                }
            } else {
                if (Db::insert(Functions::constantString('APP_CACHE_DB_TABLE'), ['name' => $cache->key, 'value' => $value ? $value : '', 'expires' => $cache->expires])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Delete Cache Data
     *
     * @param string $key Name of the Value to delete.
     *
     * @throws Exception
     *
     * @return bool
     */
    private static function deleteData(string $key): bool
    {
        $driver = Functions::constantString('APP_CACHE_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: Cache Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_CACHE_FILE_PATH');

            if (!is_dir($path)) {
                return true;
            }

            $file = $path.'/'.$key;

            if (!file_exists($file)) {
                return true;
            }

            return unlink($file);
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            return Db::delete(Functions::constantString('APP_CACHE_DB_TABLE'), ['name' => $key]);
        }

        return false;
    }
}
