<?php declare(strict_types = 1);
/**
 * This file is to handle The Router
 */

namespace SpryPhp\Provider;

/**
 * Class for Router
 */
class Router
{
    /**
     * Change the Route.
     *
     * @param string $path
     *
     * @return void
     */
    public static function go($path)
    {
        header('Location: '.$path);
        exit;
    }
}
