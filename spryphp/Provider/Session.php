<?php declare(strict_types = 1);
/**
 * This file is to handle Users
 */

namespace SpryPhp\Provider;

use SpryPhp\Model\User;
use SpryPhp\Provider\Functions;

/**
 * Class for managing and rendering users
 */
class Session
{
    /**
     * User
     *
     * @var User|null $user
     */
    public static ?User $user = null;

    /**
     * Session Id
     *
     * @var string $id
     */
    private static string $id = '';

    /**
     * Initiate the Session
     *
     * @return void
     */
    public static function start(): void
    {
        if (!defined('APP_SESSION_COOKIE_NAME')) {
            error_log('SpryPHP: APP_SESSION_COOKIE_NAME is not defined.');
        }

        if (!defined('APP_SESSION_LOGGED_IN_COOKIE_NAME')) {
            error_log('SpryPHP: APP_SESSION_LOGGED_IN_COOKIE_NAME is not defined.');
        }

        if (!defined('APP_AUTH_PASSWORD')) {
            error_log('SpryPHP: APP_AUTH_PASSWORD is not defined.');
        }

        if (!defined('APP_URI')) {
            error_log('SpryPHP: APP_URI is not defined.');
        }

        if (!defined('APP_SESSION_TTL')) {
            error_log('SpryPHP: APP_SESSION_TTL is not defined.');
        }

        if (defined('APP_SESSION_COOKIE_NAME') && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')])) {
            $token = $_COOKIE[constant('APP_SESSION_COOKIE_NAME')];

            // Check if Admin Login
            if (!empty($token) && $token === md5('admin'.constant('APP_AUTH_PASSWORD'))) {
                self::loginUser('1', 'admin', 'Admin', $token);
            }

            if (empty(self::$user->id)) {
                self::logout();
                Functions::abort('Invalid Session. Please Login Again');
            }
        } else {
            if (defined('APP_SESSION_LOGGED_IN_COOKIE_NAME') && !empty($_COOKIE[constant('APP_SESSION_LOGGED_IN_COOKIE_NAME')])) {
                self::logout();
                Functions::abort('Your Session has Expired');
            }
        }

        // Check and Validate CSRF Token
        $csrf = self::csrf();
        if ($csrf) {
            if ((Request::$method !== 'GET' && (empty($_REQUEST['csrf']) || $_REQUEST['csrf'] !== $csrf)) || (Request::$method === 'GET' && !empty($_REQUEST['csrf']) && $_REQUEST['csrf'] !== $csrf)) {
                Functions::abort('Invalid CSRF Token.');
            }
        }
    }

    /**
     * Get the User
     *
     * @return User|null
     */
    public static function user(): ?User
    {
        return self::$user;
    }

    /**
     * Login User.
     *
     * @return bool
     */
    public static function login()
    {
        if (!empty($_POST['password'])) {
            // Check if Admin Login
            if ($_POST['password'] === constant('APP_AUTH_PASSWORD')) {
                self::loginUser('1', 'admin', 'Admin', md5('admin'.constant('APP_AUTH_PASSWORD')));
                Alerts::addAlert('success', 'Logged In Successfully!');

                return true;
            }
        }

        Functions::abort('Invalid Username or Password. Please Try Again.');
    }

    /**
     * Log Out User.
     *
     * @return bool
     */
    public static function logout()
    {
        self::updateCookie();
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return defined('APP_SESSION_COOKIE_NAME') && !empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME')]) && !empty(self::$user->id) ? true : false;
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && self::$user->type === 'admin' ? true : false;
    }

    /**
     * Return a csrf token
     *
     * @return string
     */
    public static function csrf(): string
    {
        return self::$id ? sha1(self::$id) : '';
    }

    /**
     * Return TTL
     *
     * @return int
     */
    public static function getTTL(): int
    {
        return defined('APP_SESSION_TTL') && constant('APP_SESSION_TTL') ? intval(constant('APP_SESSION_TTL')) : 0;
    }

    /**
     * Setup Employee Vars
     *
     * @param string $userId
     * @param string $userType - admin | employee
     * @param string $userName
     * @param string $token
     *
     * @return void
     */
    private static function loginUser(string $userId, string $userType, string $userName, string $token)
    {
        self::updateCookie($token);
        self::$id = $token;
        self::$user = new User($userId, $userType, $userName);
    }

    /**
     * Update Cookie
     *
     * @param string $token
     *
     * @return void
     */
    private static function updateCookie(string $token = '')
    {
        if (!defined('APP_SESSION_COOKIE_NAME') || !defined('APP_SESSION_LOGGED_IN_COOKIE_NAME')) {
            error_log('SpryPHP: APP_SESSION_COOKIE_NAME and APP_SESSION_LOGGED_IN_COOKIE_NAME are not defined.');

            return;
        }

        setcookie(constant('APP_SESSION_LOGGED_IN_COOKIE_NAME'), $token ? '1' : '', $token ? 0 : (time() - 1), defined('APP_URI') ? constant('APP_URI') : '/', $_SERVER['HTTP_HOST'], true, true);
        $_COOKIE[constant('APP_SESSION_LOGGED_IN_COOKIE_NAME')] = $token ? '1' : '';
        setcookie(constant('APP_SESSION_COOKIE_NAME'), $token, $token ? (defined('APP_SESSION_TTL') && constant('APP_SESSION_TTL') ? (time() + intval(constant('APP_SESSION_TTL'))) : 0) : (time() - 1), defined('APP_URI') ? constant('APP_URI') : '/', $_SERVER['HTTP_HOST'], true, true);
        $_COOKIE[constant('APP_SESSION_COOKIE_NAME')] = $token;
    }
}
