<?php declare(strict_types=1);
/**
 * This file is to handle View Templates
 */

namespace SpryPhp\Provider;

use Exception;

/**
 * Route Instance
 */
class Route
{
    /**
     * Get Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function GET(string $path, callable $callback): void
    {
        if (Request::getMethod() === 'GET') {
            self::request($path, $callback);
        }
    }

    /**
     * POST Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function POST(string $path, callable $callback): void
    {
        if (Request::getMethod() === 'POST') {
            self::request($path, $callback);
        }
    }

    /**
     * DELETE Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function DELETE(string $path, callable $callback): void
    {
        if (Request::getMethod() === 'DELETE') {
            self::request($path, $callback);
        }
    }

    /**
     * PUT Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function PUT(string $path, callable $callback): void
    {
        if (Request::getMethod() === 'PUT') {
            self::request($path, $callback);
        }
    }

    /**
     * HEAD Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function HEAD(string $path, callable $callback): void
    {
        if (Request::getMethod() === 'HEAD') {
            self::request($path, $callback);
        }
    }

    /**
     * OPTIONS Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @throws Exception
     *
     * @return void
     */
    public static function OPTIONS(string $path, callable $callback): void
    {
        if (Request::getMethod() === 'OPTIONS') {
            self::request($path, $callback);
        }
    }

    /**
     * Change the Route.
     *
     * @param string              $path
     * @param array<string,mixed> $flashStorage Temporary Session Data for next page load.
     *
     * @return never
     */
    public static function redirect(string $path, array $flashStorage = []): never
    {
        if (headers_sent()) {
            throw new Exception('SpryPHP: Headers Already Sent. redirect() must be called before any other headers are sent.');
        }

        foreach ($flashStorage as $key => $value) {
            Session::set($key, $value, true);
        }

        header('Location: '.$path);
        exit;
    }

    /**
     * Run the Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     *
     * @return void
     */
    private static function request(string $path, callable $callback): void
    {
        $matches = null;

        $path = str_replace('\\/', '/', $path);
        $regex = '/^\/'.str_replace('/', '\\/', trim($path, '/')).'\/?$/i';
        preg_match($regex, Request::getPath(), $matches);

        if (!empty($matches[0])) {
            array_shift($matches);
            if (count($matches) === 1) {
                $response = $callback($matches[0]);
            } elseif (count($matches) > 1) {
                $response = $callback(...$matches);
            } else {
                $response = $callback();
            }

            if (is_string($response)) {
                echo $response;
            }
            if (is_array($response)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response);
            }
            if (!is_null($response)) {
                exit;
            }
        }
    }
}
