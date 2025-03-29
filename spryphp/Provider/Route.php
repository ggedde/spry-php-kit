<?php declare(strict_types = 1);
/**
 * This file is to handle View Templates
 */

namespace SpryPhp\Provider;

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
     */
    public static function GET(string $path, callable $callback)
    {
        if (Request::$method === 'GET') {
            self::request($path, $callback);
        }
    }

    /**
     * POST Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     */
    public static function POST(string $path, callable $callback)
    {
        if (Request::$method === 'POST') {
            self::request($path, $callback);
        }
    }

    /**
     * DELETE Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     */
    public static function DELETE(string $path, callable $callback)
    {
        if (Request::$method === 'DELETE') {
            self::request($path, $callback);
        }
    }

    /**
     * PUT Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     */
    public static function PUT(string $path, callable $callback)
    {
        if (Request::$method === 'PUT') {
            self::request($path, $callback);
        }
    }

    /**
     * Run the Request
     *
     * @param string   $path     - Request URI to match.
     * @param callable $callback - Callback function.
     */
    private static function request(string $path, callable $callback)
    {
        $matches = null;

        $path = str_replace('\\/', '/', $path);
        $regex = '/'.str_replace('/', '\\/', trim($path, '/')).'\/?$/i';
        preg_match($regex, Request::$pathFull, $matches);

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
