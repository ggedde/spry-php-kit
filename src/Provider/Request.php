<?php declare(strict_types=1);
/**
 * This file is to handle the Request
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Functions;
use Exception;

/**
 * Class for Request
 */
class Request
{
    /**
     * Path
     *
     * @var string $path
     */
    private static string $path = '';

    /**
     * Path with paged
     *
     * @var string $pathPaged
     */
    private static string $pathPaged = '';

    /**
     * Path with filters, but no paged or other query vars
     *
     * @var string $pathFilters
     */
    private static string $pathFilters = '';

    /**
     * Path including paged and query
     *
     * @var string $pathFull
     */
    private static string $pathFull = '';

    /**
     * Page
     *
     * @var int $page
     */
    private static int $page = 1;

    /**
     * Method
     *
     * @var string $method
     */
    private static string $method = '';

    /**
     * Filters
     *
     * @var object $filters
     */
    private static object $filters;

    /**
     * Query
     *
     * @var object $query
     */
    private static object $query;

    /**
     * QueryString
     *
     * @var string $queryString - Sanitized
     */
    private static string $queryString = '';

    /**
     * Order By
     *
     * @var string $orderBy
     */
    private static string $orderBy = 'created_at';

    /**
     * Order
     *
     * @var string $order - ASC | DESC
     */
    private static string $order = 'DESC';

    /**
     * Params
     *
     * @var object $params
     */
    private static object $params;

    /**
     * Construct the Request.
     *
     * @uses APP_REQUEST_VERIFY_CSRF
     *
     * @throws Exception
     *
     * @return void
     */
    public static function setup(): void
    {
        if (empty($_SERVER['REQUEST_URI']) || !is_string($_SERVER['REQUEST_URI'])) {
            throw new Exception('SpryPhp: Missing or Invalid Server Variable REQUEST_URI.');
        }

        self::$pathFull = $_SERVER['REQUEST_URI'];
        self::$filters = (object) [];

        $request = parse_url(self::$pathFull);

        self::$path = self::$pathPaged = empty($request['path']) || $request['path'] === '/' ? '/' : rtrim($request['path'], '/');

        if (preg_match('/(\/.*)\/[0-9]+?/', self::$path, $pageMatches)) {
            self::$path = $pageMatches[1];
        }

        if (preg_match('/(\/.*)\/([0-9]+)/', self::$pathPaged, $matches)) {
            if ($matches[2]) {
                self::$pathPaged = $matches[0];
                self::$page = intval($matches[2]);
            }
        }

        if (!empty($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])) {
            self::$method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
        }

        if (self::$method === 'HEAD') {
            self::$method = 'GET';
        }

        self::$query = (object) [];
        if (!empty($request['query'])) {
            parse_str($request['query'], $query);
            if (!empty($query)) {
                foreach ($query as $key => $value) {
                    $key = Functions::formatCamelCase(strval($key));
                    self::$query->$key = is_array($value) ? (object) array_map(function ($v) {
                        return is_string($v) ? trim($v) : $v;
                    }, $value) : trim($value);
                }
            }
        }

        if (!empty(self::$query->orderby)) {
            self::$orderBy = is_string(self::$query->orderby) ? strval(preg_replace('/[^a-zA-Z0-9\_\-]/', '', self::$query->orderby)) : '';
            self::$order = !empty(self::$query->order) && self::$query->order === 'ASC' ? 'ASC' : 'DESC';
        }

        if (!empty((array) self::$query)) {
            self::$queryString = '?';
            foreach ((array) self::$query as $key => $value) {
                if (!is_array($value) && is_scalar($value)) {
                    self::$queryString .= preg_replace('/[^a-zA-Z0-9\_\-]/', '', strval($key)).'='.preg_replace('/[^a-zA-Z0-9\_\-]/', '', strval($value)).'&';
                }
            }
            self::$queryString = rtrim(self::$queryString, '&');
        }

        if (!empty(self::$query->filter)) {
            foreach ((array) self::$query->filter as $key => $value) {
                if (!is_array($value) && is_scalar($value)) {
                    $filterKey = preg_replace('/[^a-zA-Z0-9\_\-]/', '', strval($key));
                    self::$filters->$filterKey = preg_replace('/[^a-zA-Z0-9\_\-]/', '', strval($value));
                }
            }
        }

        self::$pathFilters = self::$path;

        if (!empty((array) self::$filters)) {
            self::$queryString .= '&'.http_build_query(['filter' => self::$filters]);
            self::$pathFilters = self::$path.'?'.http_build_query(['filter' => self::$filters]);
        }

        self::$params = (object) [];
        if (self::$method === 'POST' && !empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $key = Functions::formatCamelCase($key);
                self::$params->$key = is_array($value) ? array_map(function ($v) {
                    return is_string($v) ? trim($v) : $v;
                }, $value) : (is_string($value) ? trim($value) : $value);
            }

            if (!empty(self::$params->requestMethod) && is_string(self::$params->requestMethod) && in_array(strtoupper(self::$params->requestMethod), ['PUT', 'PATCH', 'DELETE'], true)) {
                self::$method = strtoupper(self::$params->requestMethod);
            }
        }

        // Check and Validate CSRF Token
        if (Functions::constantBool('APP_REQUEST_VERIFY_CSRF', false)) {
            $csrf = Session::getCsrf();
            if ($csrf && ((self::$method !== 'GET' && (empty($_REQUEST['csrf']) || $_REQUEST['csrf'] !== $csrf)) || (self::$method === 'GET' && !empty($_REQUEST['csrf']) && $_REQUEST['csrf'] !== $csrf))) {
                Functions::abort('Invalid CSRF Token.');
            }
        }

        if (self::$method === 'GET' && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $key = Functions::formatCamelCase($key);
                self::$params->$key = is_array($value) ? array_map(function ($v) {
                    return is_string($v) ? trim($v) : $v;
                }, $value) : (is_string($value) ? trim($value) : $value);
            }
        }
    }

    /**
     * Get Path
     *
     * @return string
     */
    public static function getPath(): string
    {
        return self::$path;
    }

    /**
     * Get Path with paged
     *
     * @return string
     */
    public static function getPathPaged(): string
    {
        return self::$pathPaged;
    }

    /**
     * Construct the Query String with optional parameter to add or swap out parameters.
     *
     * @param array<string,string> $mergeParams
     *
     * @return string
     */
    public static function getPathQuery(array $mergeParams): string
    {
        $encoded = json_encode(self::$query);
        $query = $encoded ? json_decode($encoded) : (object) [];
        foreach ($mergeParams as $key => $value) {
            $query->$key = is_string($value) ? trim($value) : strval($value);
        }

        return self::$path.(is_object($query) ? '?'.http_build_query($query) : '');
    }

    /**
     * Get Path with filters, but no paged or other query vars
     *
     * @return string
     */
    public static function getPathFilters(): string
    {
        return self::$pathFilters;
    }

    /**
     * Get Path including paged and query
     *
     * @return string
     */
    public static function getPathFull(): string
    {
        return self::$pathFull;
    }

    /**
     * Get Page Number
     *
     * @return int
     */
    public static function getPage(): int
    {
        return self::$page;
    }

    /**
     * Get Method
     *
     * @return string
     */
    public static function getMethod(): string
    {
        return self::$method;
    }

    /**
     * Get Filters
     *
     * @return object
     */
    public static function getFilters(): object
    {
        return self::$filters;
    }

    /**
     * Get Query Object
     *
     * @return object
     */
    public static function getQuery(): object
    {
        return self::$query;
    }

    /**
     * Get Query String
     *
     * @return string
     */
    public static function getQueryString(): string
    {
        return self::$queryString;
    }

    /**
     * Get Order By
     *
     * @return string
     */
    public static function getOrderBy(): string
    {
        return self::$orderBy;
    }

    /**
     * Get Order
     *
     * @return string
     */
    public static function getOrder(): string
    {
        return self::$order;
    }

    /**
     * Get Params Object
     *
     * @return object
     */
    public static function getParams(): object
    {
        return self::$params;
    }
}
