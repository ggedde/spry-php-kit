<?php declare(strict_types = 1);
/**
 * This file is to handle Sessions
 */

namespace SpryPhp\Provider;

use Exception;
use SpryPhp\Provider\Functions;

/**
 * Class for managing Sessions
 */
class Session
{
    /**
     * Session Id
     *
     * @var string|null $id
     */
    private static ?string $id = null;

    /**
     * Session Type
     *
     * @var string|null $type
     */
    private static ?string $type = null;

    /**
     * Session User
     *
     * @var object|null $user
     */
    private static ?object $user = null;

    /**
     * Initiate the Session
     *
     * @uses APP_SESSION_COOKIE_NAME
     * @uses APP_SESSION_COOKIE_ACTIVE_NAME
     *
     * @throws Exception
     *
     * @return string|null
     */
    public static function start(): ?string
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME is not defined.", 1);
        }

        if (!defined('APP_SESSION_COOKIE_ACTIVE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_ACTIVE_NAME is not defined.", 1);
        }

        // Check to see if the Session is still active, but has expired.
        if (empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_ACTIVE_NAME')])) {
            self::delete();
            Functions::abort('Your Session has Expired');
        }

        return isset($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) ? $_COOKIE[constant('APP_SESSION_COOKIE_NAME')] : null;
    }

    /**
     * Sets the Session Type, Id, and User.
     *
     * @param string      $sessionId   - Unique String for Session.
     * @param string|null $sessionType - Session Type, like "admin", "user", etc.
     * @param object|null $sessionUser - Object for User. Use `null` or Anonymous object for Single User, ex. {name: "Admin"} or {name: "Anonymous"}
     *
     * @return void
     */
    public static function set(string $sessionId, ?string $sessionType = null, ?object $sessionUser = null)
    {
        self::$id   = self::makeIdFrom($sessionId);
        self::$type = $sessionType;
        self::$user = $sessionUser;
        self::updateCookie(self::$id);
    }

    /**
     * Updates and resets a Session expiration.
     *
     * @return void
     */
    public static function update()
    {
        if (!empty(self::$id)) {
            self::updateCookie(self::$id);
        }
    }

    /**
     * Delete the Session.
     *
     * @return bool
     */
    public static function delete()
    {
        self::$type = null;
        self::$id   = null;
        self::updateCookie();
    }

    /**
     * Check if the Session is active.
     *
     * @uses APP_SESSION_COOKIE_NAME
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME is not defined.", 1);
        }

        return !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty(self::$id) ? true : false;
    }

    /**
     * Get the Session Id
     *
     * @return string|null
     */
    public static function getId(): ?string
    {
        return self::$id;
    }

    /**
     * Get the Session Type
     *
     * @return string|null
     */
    public static function getType(): ?string
    {
        return self::$type;
    }

    /**
     * Get the Session User
     *
     * @return object|null
     */
    public static function getUser(): ?object
    {
        return self::$user;
    }

    /**
     * Return the csrf token
     *
     * @uses APP_AUTH_KEY
     *
     * @throws Exception
     *
     * @return string|null
     */
    public static function getCsrf(): ?string
    {
        if (!defined('APP_AUTH_KEY')) {
            throw new Exception("SpryPHP: APP_AUTH_KEY is not defined", 1);
        }

        return self::$id ? sha1(self::$id) : (constant('APP_AUTH_KEY') ? sha1(constant('APP_AUTH_KEY')) : null);
    }

    /**
     * Return TTL
     *
     * @uses APP_SESSION_TTL
     *
     * @throws Exception
     *
     * @return int
     */
    public static function getTtl(): int
    {
        if (!defined('APP_SESSION_TTL')) {
            throw new Exception("SpryPHP: APP_SESSION_TTL is not defined.", 1);
        }

        return intval(constant('APP_SESSION_TTL'));
    }

    /**
     * Makes an Authorized Session ID from user value.
     *
     * @param string $sessionId - User provided unique id.
     *
     * @uses APP_AUTH_KEY
     *
     * @throws Exception
     *
     * @return string - sha1() Response
     */
    public static function makeIdFrom(string $sessionId): string
    {
        if (!defined('APP_AUTH_KEY')) {
            throw new Exception("SpryPHP: APP_AUTH_KEY is not defined.", 1);
        }

        return sha1(constant('APP_AUTH_KEY').$sessionId);
    }

    /**
     * Update Cookie
     *
     * @param string $sessionId
     *
     * @uses APP_SESSION_COOKIE_NAME
     * @uses APP_SESSION_COOKIE_ACTIVE_NAME
     * @uses APP_SESSION_COOKIE_HTTP_ONLY
     * @uses APP_URI
     * @uses APP_SESSION_TTL
     *
     * @throws Exception
     *
     * @return void
     */
    private static function updateCookie(string $sessionId = '')
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_ACTIVE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_ACTIVE_NAME is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_HTTP_ONLY')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_HTTP_ONLY is not defined.", 1);
        }
        if (!defined('APP_SESSION_TTL')) {
            throw new Exception("SpryPHP: APP_SESSION_TTL is not defined.", 1);
        }
        if (!defined('APP_URI')) {
            throw new Exception("SpryPHP: APP_URI is not defined.", 1);
        }

        setcookie(constant('APP_SESSION_COOKIE_ACTIVE_NAME'), $sessionId ? '1' : '', $sessionId ? 0 : (time() - 1), constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
        $_COOKIE[constant('APP_SESSION_COOKIE_ACTIVE_NAME')] = $sessionId ? '1' : '';
        setcookie(constant('APP_SESSION_COOKIE_NAME'), $sessionId, $sessionId ? (constant('APP_SESSION_TTL') ? (time() + intval(constant('APP_SESSION_TTL'))) : 0) : (time() - 1), constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
        $_COOKIE[constant('APP_SESSION_COOKIE_NAME')] = $sessionId;
    }
}
