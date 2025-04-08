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
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function getFromSession()
    {
        if (!defined('APP_SESSION_COOKIE_NAME_ALERTS')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_ALERTS is not defined.", 1);
        }
        if (!empty($_COOKIE[constant('APP_SESSION_COOKIE_NAME_ALERTS')])) {
            $alerts = json_decode(base64_decode($_COOKIE[constant('APP_SESSION_COOKIE_NAME_ALERTS')]));
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
        self::updateCookie(base64_encode(json_encode(self::$alerts)), time() + 3600, true);
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
            throw new Exception("SpryPHP: APP_URI is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_NAME_ALERTS')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_NAME_ALERTS is not defined.", 1);
        }
        if (!defined('APP_SESSION_COOKIE_HTTP_ONLY')) {
            throw new Exception("SpryPHP: APP_SESSION_COOKIE_HTTP_ONLY is not defined.", 1);
        }
        if (!headers_sent()) {
            setcookie(constant('APP_SESSION_COOKIE_NAME_ALERTS'), $value, $time, constant('APP_URI'), $_SERVER['HTTP_HOST'], true, !empty(constant('APP_SESSION_COOKIE_HTTP_ONLY')));
            if ($setGlobal) {
                $_COOKIE[constant('APP_SESSION_COOKIE_NAME_ALERTS')] = $value;
            }
        }
    }
}
