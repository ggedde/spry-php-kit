<?php declare(strict_types = 1);
/**
 * This file is to handle Sessions
 */

namespace SpryPhp\Provider;

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
     * Initiate the Session
     *
     * @return string|null
     */
    public static function start(): ?string
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            error_log('SpryPHP: APP_SESSION_COOKIE_NAME is not defined.');
        }

        if (!defined('APP_SESSION_COOKIE_ACTIVE_NAME')) {
            error_log('SpryPHP: APP_SESSION_COOKIE_ACTIVE_NAME is not defined.');
        }

        // Check to see if the Session is still active, but has expired.
        if (defined('APP_SESSION_COOKIE_NAME') && defined('APP_SESSION_COOKIE_ACTIVE_NAME') && empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_ACTIVE_NAME')])) {
            self::delete();
            Functions::abort('Your Session has Expired');
        }

        return isset($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) ? $_COOKIE[constant('APP_SESSION_COOKIE_NAME')] : null;
    }

    /**
     * Sets the Session Type and Id.
     *
     * @param string $sessionType - Session Type, like "admin", "user", etc.
     * @param string $sessionId   - Unique String for Session.
     *
     * @return void
     */
    public static function set(string $sessionType, string $sessionId)
    {
        self::$type = $sessionType;
        self::$id   = self::makeIdFrom($sessionId);
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
     * @return bool
     */
    public static function isActive(): bool
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            error_log('SpryPHP: APP_SESSION_COOKIE_NAME is not defined.');
        }

        return defined('APP_SESSION_COOKIE_NAME') && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty(self::$id) ? true : false;
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
     * Return the csrf token
     *
     * @return string|null
     */
    public static function getCsrf(): ?string
    {
        return self::$id ? sha1(self::$id) : (defined('APP_AUTH_KEY') && constant('APP_AUTH_KEY') ? sha1(constant('APP_AUTH_KEY')) : null);
    }

    /**
     * Return TTL
     *
     * @return int
     */
    public static function getTtl(): int
    {
        if (!defined('APP_SESSION_TTL')) {
            error_log('SpryPHP: APP_SESSION_TTL is not defined.');
        }

        return defined('APP_SESSION_TTL') && constant('APP_SESSION_TTL') ? intval(constant('APP_SESSION_TTL')) : 0;
    }

    /**
     * Makes an Authorized Session ID from user value.
     *
     * @param string $sessionId - User provided unique id.
     *
     * @return string
     */
    public static function makeIdFrom(string $sessionId): string
    {
        if (!defined('APP_AUTH_KEY')) {
            error_log('SpryPHP: APP_AUTH_KEY is not defined.');
        }

        return sha1(constant('APP_AUTH_KEY').$sessionId);
    }

    /**
     * Update Cookie
     *
     * @param string $sessionId
     *
     * @return void
     */
    private static function updateCookie(string $sessionId = '')
    {
        if (!defined('APP_SESSION_COOKIE_NAME') || !defined('APP_SESSION_COOKIE_ACTIVE_NAME')) {
            error_log('SpryPHP: APP_SESSION_COOKIE_NAME and APP_SESSION_COOKIE_ACTIVE_NAME are not defined.');

            return;
        }

        setcookie(constant('APP_SESSION_COOKIE_ACTIVE_NAME'), $sessionId ? '1' : '', $sessionId ? 0 : (time() - 1), defined('APP_URI') ? constant('APP_URI') : '/', $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
        $_COOKIE[constant('APP_SESSION_COOKIE_ACTIVE_NAME')] = $sessionId ? '1' : '';
        setcookie(constant('APP_SESSION_COOKIE_NAME'), $sessionId, $sessionId ? (defined('APP_SESSION_TTL') && constant('APP_SESSION_TTL') ? (time() + intval(constant('APP_SESSION_TTL'))) : 0) : (time() - 1), defined('APP_URI') ? constant('APP_URI') : '/', $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
        $_COOKIE[constant('APP_SESSION_COOKIE_NAME')] = $sessionId;
    }
}
