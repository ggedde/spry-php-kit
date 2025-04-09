<?php declare(strict_types = 1);
/**
 * This file is to handle Alerts
 */

namespace SpryPhp\Provider;

use Exception;
use SpryPhp\Model\Alert;

/**
 * Class for managing and rendering Alerts
 */
class Alerts
{
    /**
     * Alerts
     *
     * @var Alert[] $alerts
     */
    private static array $alerts = [];

    /**
     * Setup Alerts
     *
     * @return bool
     */
    public static function setup()
    {
        if (!defined('APP_SESSION_COOKIE_NAME_ALERTS')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_ALERTS is not defined.");
        }
        if (!empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME_ALERTS')])) {
            $alerts = json_decode(base64_decode($_COOKIE[constant('APP_SESSION_COOKIE_NAME_ALERTS')]));
            if (!empty($alerts) && is_array($alerts)) {
                self::$alerts = $alerts;
            }
        }
    }

    /**
     * Get All Alerts
     *
     * @return Alert[]
     */
    public static function get(): array
    {
        return self::$alerts;
    }

    /**
     * Set an Alert
     *
     * @param string $type    - Type of Alert - error | info | success | warning
     * @param string $message - Message for Alert
     *
     * @return void
     */
    public static function set(string $type, string $message): void
    {
        if (headers_sent()) {
            throw new Exception(sprintf('SpryPHP: Headers Already Sent Alert: %s', $message));
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

        self::updateCookie(base64_encode(json_encode(self::$alerts)), time() + 3600, true);
    }

    /**
     * Clear All Alerts
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$alerts = [];
        self::updateCookie('', time() - 1);
    }

    /**
     * Update Cookie
     *
     * @param string $value
     * @param int    $time
     * @param bool   $setGlobal
     *
     * @throws Exception
     *
     * @return void
     */
    private static function updateCookie(string $value, int $time, $setGlobal = false): void
    {
        if (!defined('APP_URI')) {
            throw new Exception("SpryPHP: APP_URI is not defined.");
        }
        if (!defined('APP_SESSION_COOKIE_NAME_ALERTS')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_ALERTS is not defined.");
        }
        if (!defined('APP_SESSION_COOKIE_HTTP_ONLY')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_HTTP_ONLY is not defined.");
        }
        if (!headers_sent()) {
            setcookie(constant('APP_SESSION_COOKIE_NAME_ALERTS'), $value, $time, constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
            if ($setGlobal) {
                $_COOKIE[constant('APP_SESSION_COOKIE_NAME_ALERTS')] = $value;
            }
        }
    }
}
