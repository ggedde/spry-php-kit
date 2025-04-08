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
     * Session User
     *
     * @var object|null $user
     */
    private static ?object $user = null;

    /**
     * Initiate the Session
     *
     * @uses APP_SESSION_COOKIE_NAME
     * @uses APP_SESSION_COOKIE_NAME_ACTIVE
     *
     * @throws Exception
     *
     * @return string|null
     */
    public static function start(): string
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME is not defined.", 1);
        }

        if (!defined('APP_SESSION_COOKIE_NAME_ACTIVE')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_ACTIVE is not defined.", 1);
        }

        // Check to see if the Session is still active, but has expired.
        if (empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME_ACTIVE')])) {
            self::delete();
            Functions::abort('Your Session has Expired');
        }

        // Check to see if we already have a session, if so then lets use it.
        if (isset($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')])) {
            self::$id = $_COOKIE[constant('APP_SESSION_COOKIE_NAME')];

            return self::$id;
        }

        // If no session, then lets create a new Guest Session.
        self::$id = self::makeIdFrom(microtime());
        self::updateCookie(self::$id, self::getTtl(true), true);

        return self::$id;
    }

    /**
     * Sets the Session Id and User.
     *
     * @param string      $sessionUniqueString String to construct the Session ID.
     * @param object|null $sessionUser         Object for User. Use `null` for Guest or Anonymous object for Single User, ex. {name: "Admin"} or {name: "Anonymous"}
     * @param int|null    $sessionTtl          Session TTL. Uses APP_SESSION_TTL as Default
     *
     * @uses APP_SESSION_TTL (for Default)
     *
     * @return void
     */
    public static function set(string $sessionUniqueString, ?object $sessionUser = null, ?int $sessionTtl = null)
    {
        self::$id = self::makeIdFrom($sessionUniqueString);
        if ($sessionUser) {
            self::$user = $sessionUser;
        }
        self::update($sessionUser, $sessionTtl);
    }

    /**
     * Update the Session Id and User.
     *
     * @param object|null $sessionUser Object for User. Use `null` for Guest or Anonymous object for Single User, ex. {name: "Admin"} or {name: "Anonymous"}
     * @param int|null    $sessionTtl  Session TTL. Uses APP_SESSION_TTL as Default
     *
     * @uses APP_SESSION_TTL (for Default)
     *
     * @return void
     */
    public static function update(?object $sessionUser = null, ?int $sessionTtl = null)
    {
        if (!self::$id) {
            self::$id = self::makeIdFrom(json_encode($sessionUser), strval($sessionTtl));
        }
        if ($sessionUser) {
            self::$user = $sessionUser;
        }
        self::updateCookie(self::$id, is_null($sessionTtl) ? self::getTtl() : intval($sessionTtl));
    }

    /**
     * Delete the Session.
     *
     * @return bool
     */
    public static function delete()
    {
        self::$id   = null;
        self::$user = null;
        self::updateCookie();
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
     * @param bool $guest Whether to get Guest Session TTL
     *
     * @uses APP_SESSION_TTL
     *
     * @throws Exception
     *
     * @return int
     */
    public static function getTtl(bool $guest = false): int
    {
        if (!defined('APP_SESSION_TTL')) {
            throw new Exception("SpryPHP: APP_SESSION_TTL is not defined.", 1);
        }

        if (!defined('APP_SESSION_TTL_GUEST')) {
            throw new Exception("SpryPHP: APP_SESSION_TTL_GUEST is not defined.", 1);
        }

        return intval(constant($guest ? 'APP_SESSION_TTL_GUEST' : 'APP_SESSION_TTL'));
    }

    /**
     * Makes an Authorized Session ID from user value.
     *
     * @param string $sessionUniqueString User provided unique string.
     *
     * @uses APP_AUTH_KEY
     *
     * @throws Exception
     *
     * @return string - sha1() Response
     */
    public static function makeIdFrom(string $sessionUniqueString): string
    {
        if (!defined('APP_AUTH_KEY')) {
            throw new Exception("SpryPHP: APP_AUTH_KEY is not defined.", 1);
        }

        return sha1(constant('APP_AUTH_KEY').$sessionUniqueString);
    }

    /**
     * Update Cookie
     *
     * @param string $sessionId    Unique ID for Session.
     * @param int    $sessionTtl   TTL for Session.
     * @param bool   $sessionGuest Whether the session is a Guest Session.
     *
     * @uses APP_SESSION_COOKIE_NAME
     * @uses APP_SESSION_COOKIE_NAME_ACTIVE
     * @uses APP_SESSION_COOKIE_HTTP_ONLY
     * @uses APP_URI
     *
     * @throws Exception
     *
     * @return void
     */
    private static function updateCookie(string $sessionId = '', int $sessionTtl = 0, bool $sessionGuest = false)
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_NAME_ACTIVE')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_ACTIVE is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_HTTP_ONLY')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_HTTP_ONLY is not defined.", 1);
        }
        if (!defined('APP_URI')) {
            throw new Exception("SpryPHP: APP_URI is not defined.", 1);
        }

        if (!$sessionGuest) {
            setcookie(constant('APP_SESSION_COOKIE_NAME_ACTIVE'), $sessionId ? '1' : '', $sessionId ? 0 : (time() - 1), constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
            $_COOKIE[constant('APP_SESSION_COOKIE_NAME_ACTIVE')] = $sessionId ? '1' : '';
        }
        setcookie(constant('APP_SESSION_COOKIE_NAME'), $sessionId, $sessionId ? ($sessionTtl ? (time() + intval($sessionTtl)) : 0) : (time() - 1), constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
        $_COOKIE[constant('APP_SESSION_COOKIE_NAME')] = $sessionId;
    }
}
