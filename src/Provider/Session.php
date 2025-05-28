<?php declare(strict_types=1);
/**
 * This file is to handle Sessions
 */

namespace SpryPhp\Provider;

use Exception;
use SpryPhp\Model\Alert;

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
     * User Key
     *
     * @var string $userKey
     */
    private static string $userKey = '__USER__';

    /**
     * Session Alerts
     *
     * @var Alert[] $alerts
     */
    private static array $alerts = [];

    /**
     * Alerts Key
     *
     * @var string $alertsKey
     */
    private static string $alertsKey = '__ALERTS__';

    /**
     * Start the Session
     *
     * @uses APP_SESSION_COOKIE_NAME
     * @uses APP_SESSION_LOGGED_IN_COOKIE_NAME
     * @uses APP_SESSION_TTL
     *
     * @throws Exception
     *
     * @return void
     */
    public static function start(): void
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. Session must be started before any other headers are sent.');
        }

        if (!defined('APP_SESSION_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME is not defined.");
        }

        if (!defined('APP_SESSION_LOGGED_IN_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_LOGGED_IN_COOKIE_NAME is not defined.");
        }

        session_start([
            'name' => Functions::constantString('APP_SESSION_COOKIE_NAME'),
            'cookie_httponly' => true,
            'cookie_lifetime' => Functions::constantInt('APP_SESSION_TTL'),
            'cookie_samesite' => defined('APP_SESSION_COOKIE_SAMESITE') ? Functions::constantString('APP_SESSION_COOKIE_SAMESITE') : 'Lax',
            'cookie_secure' => true,
            'gc_maxlifetime' => Functions::constantInt('APP_SESSION_TTL'),
            'referer_check' => $_SERVER['HTTP_HOST'],
            'use_strict_mode' => true,
            'read_and_close' => true,
        ]);

        self::$id = session_id() ?: null;

        if (!empty($_SESSION[self::$userKey]) && is_object($_SESSION[self::$userKey])) {
            self::$user = $_SESSION[self::$userKey];
        }

        if (!empty($_SESSION[self::$alertsKey]) && is_array($_SESSION[self::$alertsKey])) {
            foreach ($_SESSION[self::$alertsKey] as $alert) {
                if (is_object($alert) && isset($alert->type, $alert->message) && is_string($alert->type) && is_string($alert->message)) {
                    self::$alerts[] = new Alert($alert->type, $alert->message);
                }
            }
        }

        // Check to see if the Session is still active, but has expired.
        if (!self::getUser() && !empty($_COOKIE[Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_NAME')])) {
            self::logoutUser();
            self::addAlert('error', 'Your Session has Expired');
        }
    }

    /**
     * Return a Value from the Session by key
     *
     * @param string $key Name of the Value to retrieve.
     *
     * @throws Exception
     *
     * @return mixed
     */
    public static function get(string $key): mixed
    {
        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when getting a Session value.');
        }

        return $_SESSION[$key] ?? null;
    }

    /**
     * Set a Session value by key
     *
     * @param string $key   Name of the Value to set.
     * @param mixed  $value Value to set.
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function set(string $key, mixed $value): bool
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. Setting a Session value must be done before any other headers are sent.');
        }

        if (!self::$id) {
            throw new Exception('SpryPHP: Session must be started before you can set any session data.');
        }

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when setting a Session value.');
        }

        session_start();
        $_SESSION[$key] = $value;
        session_write_close();

        return $_SESSION[$key] === $value;
    }

    /**
     * Delete a Session value by key
     *
     * @param string $key Name of the Value to set.
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function delete(string $key): bool
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. Deleting a Session value must be done before any other headers are sent.');
        }

        if (empty($key)) {
            throw new Exception('SpryPHP: Key must not be empty when deleting from Session.');
        }

        session_start();
        $_SESSION[$key] = null;
        unset($_SESSION[$key]);
        session_write_close();

        return !isset($_SESSION[$key]);
    }

    /**
     * Clear the Session.
     *
     * @return bool
     */
    public static function destroy(): bool
    {
        self::$id   = null;
        self::$user = null;
        self::$alerts = [];

        return session_destroy();
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
     * Get Alerts
     *
     * @return Alert[]
     */
    public static function getAlerts(): array
    {
        return self::$alerts;
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
            throw new Exception("SpryPHP: APP_AUTH_KEY is not defined");
        }

        return self::$id ? hash('sha256', self::$id) : (Functions::constantString('APP_AUTH_KEY') ? hash('sha256', Functions::constantString('APP_AUTH_KEY')) : null);
    }

    /**
     * Add an Alert
     *
     * @param string $type    - Type of Alert - error | info | success | warning
     * @param string $message - Message for Alert
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function addAlert(string $type, string $message): bool
    {
        if (headers_sent()) {
            throw new Exception(sprintf('SpryPHP: Headers Already Sent. Alerts must be added before any other headers have been sent. Alert: %s', $message));
        }

        if (empty($type) || empty($message)) {
            throw new Exception('SpryPHP: Alert missing type or message.');
        }

        $hasAlert = false;

        foreach (self::$alerts as $alert) {
            if ($type === $alert->type && $message === $alert->message) {
                $hasAlert = true;
            }
        }

        if (!$hasAlert) {
            self::$alerts[] = new Alert($type, $message);
        }

        return self::set(self::$alertsKey, self::$alerts);
    }

    /**
     * Clear All Alerts
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function clearAlerts(): bool
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. Alerts must be cleared before any other headers have been sent.');
        }

        self::$alerts = [];

        return self::set(self::$alertsKey, self::$alerts);
    }

    /**
     * Login a User
     *
     * @param object $user Object for User. For Anonymous object for Single User, ex. {name: "Admin"} or {name: "Anonymous"}
     *
     * @uses APP_SESSION_LOGGED_IN_COOKIE_NAME
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function loginUser(object $user): bool
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. loginUser must be called before any other headers are sent.');
        }

        if (!defined('APP_SESSION_LOGGED_IN_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_LOGGED_IN_COOKIE_NAME is not defined.");
        }

        self::$user = $user;
        self::set(self::$userKey, $user);

        $samesite = 'Lax';
        if (defined('APP_SESSION_COOKIE_SAMESITE')) {
            $samesite = match (strtolower(Functions::constantString('APP_SESSION_COOKIE_SAMESITE'))) {
                'none' => 'None',
                'strict' => 'Strict',
                default => 'Lax',
            };
        }

        $cookieOptions = [
            'expires' => 0,
            'path' => defined('APP_URI') ? Functions::constantString('APP_URI') : '/',
            'domain' => is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'secure' => true,
            'httponly' => !empty(Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_HTTP_ONLY')),
            'samesite' => $samesite,
        ];

        setcookie(Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_NAME'), '1', $cookieOptions);
        $_COOKIE[Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_NAME')] = '1';

        return self::isUserLoggedIn();
    }

    /**
     * Check if User is Logged In
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isUserLoggedIn(): bool
    {
        if (!defined('APP_SESSION_LOGGED_IN_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_LOGGED_IN_COOKIE_NAME is not defined.");
        }

        return !empty(self::$user) && !empty($_COOKIE[Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_NAME')]);
    }

    /**
     * Check if User is Logged In
     *
     * @uses APP_SESSION_LOGGED_IN_COOKIE_NAME
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function logoutUser(): bool
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. loginUser must be called before any other headers are sent.');
        }

        if (!defined('APP_SESSION_LOGGED_IN_COOKIE_NAME')) {
            throw new Exception("SpryPHP: APP_SESSION_LOGGED_IN_COOKIE_NAME is not defined.");
        }

        $cookieName = Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_NAME');

        if (!$cookieName) {
            throw new Exception("SpryPHP: APP_SESSION_LOGGED_IN_COOKIE_NAME is invalid.");
        }

        self::$user = null;
        self::delete(self::$userKey);

        $samesite = 'Lax';
        if (defined('APP_SESSION_COOKIE_SAMESITE')) {
            $samesite = match (strtolower(Functions::constantString('APP_SESSION_COOKIE_SAMESITE'))) {
                'none' => 'None',
                'strict' => 'Strict',
                default => 'Lax',
            };
        }

        $appUri = Functions::constantString('APP_URI', '/');

        $cookieOptions = [
            'expires' => time() - 1,
            'path' => $appUri,
            'domain' => is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'secure' => true,
            'httponly' => !empty(Functions::constantString('APP_SESSION_LOGGED_IN_COOKIE_HTTP_ONLY')),
            'samesite' => $samesite,
        ];

        setcookie($cookieName, '', $cookieOptions);
        unset($_COOKIE[$cookieName]);

        return !self::isUserLoggedIn();
    }
}
