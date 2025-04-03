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
     * Cookie name for Alerts
     * Alerts need to be persistent, so they are stored in a cookie.
     *
     * @var string $cookieName
     */
    private static string $cookieName = 'ac-alerts';

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function getFromSession()
    {
        if (!empty($_COOKIE[self::$cookieName])) {
            $alerts = json_decode(base64_decode($_COOKIE[self::$cookieName]));
            if (!empty($alerts) && is_array($alerts)) {
                self::$alerts = $alerts;
            }
        }
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
     * Get User Type
     *
     * @return void
     */
    public static function removeAlerts(): void
    {
        self::$alerts = [];
        self::dumpAlerts();
    }

    /**
     * Add Alert
     *
     * @param string $type    - Type of Alert - error | info | success | warning
     * @param string $message - Message for Alert
     *
     * @return void
     */
    public static function addAlert(string $type, string $message): void
    {
        if (headers_sent()) {
            throw new Exception(sprintf('SpryPHP: Headers Already Sent Alert: %s', $message), 1);
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

        self::storeAlerts();
    }

    /**
     * Store Alerts
     * Add Alerts to Session
     *
     * @throws Exception
     *
     * @return void
     */
    private static function storeAlerts(): void
    {
        if (!defined('APP_URI')) {
            throw new Exception("SpryPHP: APP_URI is not defined.", 1);
        }
        if (!headers_sent()) {
            $cookieValue = base64_encode(json_encode(self::$alerts));
            setcookie(self::$cookieName, $cookieValue, time() + 3600, constant('APP_URI'), $_SERVER['HTTP_HOST'], true, true);
            $_COOKIE[self::$cookieName] = $cookieValue;
        }
    }

    /**
     * Dump Alerts
     * Remove Alerts from Session So they don't show up again.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function dumpAlerts(): void
    {
        if (!defined('APP_URI')) {
            throw new Exception("SpryPHP: APP_URI is not defined.", 1);
        }
        if (!headers_sent()) {
            setcookie(self::$cookieName, '', time() + 1, constant('APP_URI'), $_SERVER['HTTP_HOST'], true, true);
        }
    }
}
