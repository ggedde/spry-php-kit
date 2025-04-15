<?php declare(strict_types = 1);
/**
 * This file is to handle the Request
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Functions;

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
    public static string $path = '';

    /**
     * Path with paged
     *
     * @var string $pathPaged
     */
    public static string $pathPaged = '';

    /**
     * Path with query, but no paged
     *
     * @var string $pathQuery
     */
    public static string $pathQuery = '';

    /**
     * Path with filters, but no paged or other query vars
     *
     * @var string $pathFilters
     */
    public static string $pathFilters = '';

    /**
     * Path including paged and query
     *
     * @var string $pathFull
     */
    public static string $pathFull = '';

    /**
     * Page
     *
     * @var int $page
     */
    public static int $page = 1;

    /**
     * Method
     *
     * @var string $method
     */
    public static string $method = '';

    /**
     * Filters
     *
     * @var object $filters
     */
    public static object $filters;

    /**
     * Query
     *
     * @var object $query
     */
    public static object $query;

    /**
     * QueryString
     *
     * @var string $queryString - Sanitized
     */
    public static string $queryString = '';

    /**
     * Order By
     *
     * @var string $orderBy
     */
    public static string $orderBy = 'created_at';

    /**
     * Order
     *
     * @var string $order - ASC | DESC
     */
    public static string $order = 'DESC';

    /**
     * Params
     *
     * @var object $params
     */
    public static object $params;

    /**
     * Construct the Request.
     *
     * @uses APP_REQUEST_VERIFY_CSRF
     *
     * @return void
     */
    public static function setup()
    {
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

        if (!empty($_SERVER['REQUEST_METHOD'])) {
            self::$method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
        }

        if (self::$method === 'HEAD') {
            self::$method = 'GET';
        }

        self::$query = (object) [];
        if (!empty($request['query'])) {
            parse_str($request['query'], $query);
            if (!empty($query) && is_array($query)) {
                foreach ($query as $key => $value) {
                    $key = Functions::convertToCamelCase($key);
                    self::$query->$key = is_array($value) ? (object) array_map('trim', $value) : trim($value);
                }
            }
        }

        if (!empty(self::$query->orderby)) {
            self::$orderBy = preg_replace('/[^a-zA-Z0-9\_\-]/', '', self::$query->orderby);
            self::$order = !empty(self::$query->order) && self::$query->order === 'ASC' ? 'ASC' : 'DESC';
        }

        if (!empty(self::$query)) {
            self::$queryString = '?';
            foreach (self::$query as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    self::$queryString .= preg_replace('/[^a-zA-Z0-9\_\-]/', '', $key).'='.preg_replace('/[^a-zA-Z0-9\_\-]/', '', $value).'&';
                }
            }
            self::$queryString = rtrim(self::$queryString, '&');
        }

        if (!empty(self::$query->filter)) {
            foreach (self::$query->filter as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    $filterKey = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $key);
                    self::$filters->$filterKey = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $value);
                }
            }
        }

        self::$pathFilters = self::$path;

        if (!empty(self::$filters)) {
            self::$queryString .= '&'.http_build_query(['filter' => !empty(self::$filters) ? self::$filters : []]);
            self::$pathFilters = self::$path.(!empty(self::$filters) ? '?'.http_build_query(['filter' => !empty(self::$filters) ? self::$filters : []]) : '');
        }

        self::$pathQuery = self::$path.self::$queryString;

        self::$params = (object) [];
        if (self::$method === 'POST' && !empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $key = Functions::convertToCamelCase($key);
                self::$params->$key = is_array($value) ? array_map('trim', $value) : trim($value);
            }

            if (!empty(self::$params->requestMethod) && in_array(strtoupper(self::$params->requestMethod), ['PUT', 'PATCH', 'DELETE'], true)) {
                self::$method = strtoupper(self::$params->requestMethod);
            }
        }

        // Check and Validate CSRF Token
        if (defined('APP_REQUEST_VERIFY_CSRF') && !empty(constant('APP_REQUEST_VERIFY_CSRF'))) {
            $csrf = Session::getCsrf();
            if ($csrf && ((self::$method !== 'GET' && (empty($_REQUEST['csrf']) || $_REQUEST['csrf'] !== $csrf)) || (self::$method === 'GET' && !empty($_REQUEST['csrf']) && $_REQUEST['csrf'] !== $csrf))) {
                Functions::abort('Invalid CSRF Token.'.self::$method);
            }
        }

        if (self::$method === 'GET' && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $key = Functions::convertToCamelCase($key);
                self::$params->$key = is_array($value) ? array_map('trim', $value) : trim($value);
            }
        }
    }

    /**
     * Construct the Query String with optional parameter to add or swap out.
     *
     * @param array $params
     *
     * @return string
     */
    public static function getQueryPath(array $params): string
    {
        $query = json_decode(json_encode(self::$query));

        foreach ($params as $key => $value) {
            $query->$key = $value;
        }

        return self::$path.'?'.http_build_query($query);
    }
}
