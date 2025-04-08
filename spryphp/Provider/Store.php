<?php declare(strict_types = 1);
/**
 * This file is to handle the Store Provider
 */

namespace SpryPhp\Provider;

use Exception;

/**
 * Class for managing Store
 */
class Store
{
    /**
     * Store
     *
     * @var object $store
     */
    private static ?object $store = null;

    /**
     * Initiate the Store
     *
     * @uses APP_SESSION_COOKIE_NAME_STORE
     *
     * @throws Exception
     *
     * @return string|null
     */
    public static function setup(): void
    {
        if (!defined('APP_SESSION_COOKIE_NAME_STORE')) {
            throw new Exception('SpryPHP: APP_SESSION_COOKIE_NAME_STORE is not defined.', 1);
        }

        self::$store = (object) [];
        if (isset($_COOKIE[constant('APP_SESSION_COOKIE_NAME_STORE')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME_STORE')])) {
            $store = json_decode(base64_decode($_COOKIE[constant('APP_SESSION_COOKIE_NAME_STORE')]));
            if (!empty($store) && is_object($store)) {
                self::$store = $store;
            }
        }
    }

    /**
     * Sets the Store name and Value.
     *
     * @param string $name  Name of Store Value to Set.
     * @param mixed  $value Value of Store to Set.
     *
     * @uses APP_SESSION_TTL_STORE
     *
     * @throws Exception
     *
     * @return void
     */
    public static function set(string $name, $value): void
    {
        if (!defined('APP_SESSION_TTL_STORE')) {
            throw new Exception('SpryPHP: APP_SESSION_TTL_STORE is not defined.', 1);
        }

        $name = preg_replace('/[^A-Z_]/', '', str_replace(' ', '_', strtoupper(trim($name))));
        self::$store->$name = $value;

        $ttl = intval(constant('APP_SESSION_TTL_STORE'));
        self::updateCookie(base64_encode(json_encode(self::$store)), $ttl ? time() + $ttl : 0);
    }

    /**
     * Get a Store Value.
     *
     * @param string $name Name of Store Value to retrieve.
     *
     * @return mixed
     */
    public static function get(string $name): mixed
    {
        $name = preg_replace('/[^A-Z_]/', '', str_replace(' ', '_', strtoupper(trim($name))));
        if (isset(self::$store->$name)) {
            return self::$store->$name;
        }

        return null;
    }

    /**
     * Clears the Store
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$store = (object) [];
        self::updateCookie('', time() - 1);
    }

    /**
     * Update Store Cookie
     *
     * @param string $data Data for Store.
     * @param int    $ttl  TTL for Store.
     *
     * @uses APP_SESSION_COOKIE_NAME_STORE
     * @uses APP_SESSION_COOKIE_HTTP_ONLY
     * @uses APP_URI
     *
     * @throws Exception
     *
     * @return void
     */
    private static function updateCookie(string $data, int $ttl)
    {
        if (!defined('APP_SESSION_COOKIE_NAME_STORE')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_STORE is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_HTTP_ONLY')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_HTTP_ONLY is not defined.", 1);
        }
        if (!defined('APP_URI')) {
            throw new Exception("SpryPHP: APP_URI is not defined.", 1);
        }

        setcookie(constant('APP_SESSION_COOKIE_NAME_STORE'), $data, $ttl, constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
    }
}
