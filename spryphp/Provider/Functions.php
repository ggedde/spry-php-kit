<?php declare(strict_types=1);
/**
 * Global Functions file
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Alerts;
use SpryPhp\Provider\Request;
use SpryPhp\Provider\Router;
use SpryPhp\Provider\Validator;

/**
 * Function Class
 * Provides general functions
 */
class Functions
{

    /**
     * Basic Dump function
     *
     * @param mixed ...$data
     *
     * @return void
     */
    public static function d(...$data): void // phpcs:ignore
    {
        if (!empty($data) && is_array($data) && count($data) === 1) {
            $data = $data[0];
        }
        ob_start();
        if ($data) {
            print_r($data);
            echo "\n";
        }
        if (defined('APP_DEBUG') && !empty(constant('APP_DEBUG'))) {
            echo '<span style="color:#975;">';
            debug_print_backtrace(0);
            echo '</span>';
        }
        $contents = ob_get_contents();
        ob_end_clean();

        echo '<pre>';
        if (!defined('APP_PATH_ROOT')) {
            error_log('SpryPHP: APP_PATH_ROOT is not defined.');
            echo $contents;
        } else {
            echo str_replace(dirname(constant('APP_PATH_ROOT')), '', str_replace(constant('APP_PATH_ROOT'), '', $contents));
        }
        echo '</pre>';
    }

    /**
     * Basic Die and Dump function
     *
     * @param mixed ...$data
     *
     * @return void
     */
    public static function dd(...$data): void // phpcs:ignore
    {
        self::d(...$data);
        exit;
    }

    /**
     * Logout of Session and abort current action with a Message.
     *
     * @param string $error
     *
     * @return void
     */
    public static function abort($error): void // phpcs:ignore
    {
        if ($error) {
            Alerts::addAlert('error', $error);
            if (!defined('APP_URI_LOGIN') || !defined('APP_URI_LOGOUT')) {
                error_log('SpryPHP: No APP_URI_LOGIN or APP_URI_LOGOUT defined.');
            } elseif (!in_array(Request::$path, [constant('APP_URI_LOGIN'), constant('APP_URI_LOGOUT')], true)) {
                Router::go(constant('APP_URI_LOGIN'));
            }
        }
    }

    /**
     * Safe Value
     *
     * @param string $value
     *
     * @return string
     */
    public static function esc($value): string // phpcs:ignore
    {
        return addslashes(str_replace(['"', '&#039;'], ['&#34;', "'"], htmlspecialchars(stripslashes(strip_tags($value)), ENT_NOQUOTES, "UTF-8", false)));
    }

    /**
     * Use the Validator to Validate params
     *
     * @param object|null $params If null then it will try and ge the Params from Request::$params
     *
     * @return Validator
     */
    function validate(?object $params = null): Validator // phpcs:ignore
    {
        return new Validator(!is_null($params) ? $params : Request::$params);
    }

    /**
     * Convert to CamelCase
     *
     * @param string $data
     *
     * @return string
     */
    public static function convertToCamelCase($data)
    {
        if (!is_string($data)) {
            return $data;
        }

        $str = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $data)));
        $str[0] = strtolower($str[0]);

        return $str;
    }

    /**
     * Converts Keys to SnakeCase
     *
     * @param string $data
     *
     * @access public
     *
     * @return string
     */
    public static function convertToSnakeCase($data)
    {
        if (!is_string($data)) {
            return $data;
        }

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $data));
    }

    /**
     * List of States and abbreviations.
     *
     * @return array
     */
    public static function getStates() // phpcs:ignore
    {
        return array(
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AS' => 'American Samoa',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DC' => 'District of Columbia',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'GU' => 'Guam',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'PR' => 'Puerto Rico',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VI' => 'Virgin Islands',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        );
    }
}
