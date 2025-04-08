<?php declare(strict_types = 1);
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
        if (!is_callable($callback)) {
            throw new Exception("SpryPHP: \$callback in your GET Route is not callable.", 1);
        }
        if (Request::$method === 'GET') {
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
        if (!is_callable($callback)) {
            throw new Exception("SpryPHP: \$callback in your POST Route is not callable.", 1);
        }
        if (Request::$method === 'POST') {
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
        if (!is_callable($callback)) {
            throw new Exception("SpryPHP: \$callback in your DELETE Route is not callable.", 1);
        }
        if (Request::$method === 'DELETE') {
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
        if (!is_callable($callback)) {
            throw new Exception("SpryPHP: \$callback in your PUT Route is not callable.", 1);
        }
        if (Request::$method === 'PUT') {
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
        if (!is_callable($callback)) {
            throw new Exception("SpryPHP: \$callback in your HEAD Route is not callable.", 1);
        }
        if (Request::$method === 'HEAD') {
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
        if (!is_callable($callback)) {
            throw new Exception("SpryPHP: \$callback in your OPTIONS Route is not callable.", 1);
        }
        if (Request::$method === 'OPTIONS') {
            self::request($path, $callback);
        }
    }

    /**
     * Change the Route.
     *
     * @param string $path
     *
     * @return void
     */
    public static function goTo($path)
    {
        header('Location: '.$path);
        exit;
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
