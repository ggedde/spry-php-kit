<?php declare(strict_types=1);
/**
 * Global Functions file
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Alerts;
use SpryPhp\Provider\Request;
use SpryPhp\Provider\Route;

/**
 * Function Class
 * Provides general functions
 */
class Functions
{

    /**
     * Basic Dump function
     * Uses: APP_DEBUG, APP_PATH_ROOT
     *
     * @param mixed ...$data
     *
     * @return void
     */
    public static function d(...$data): void
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
     * Uses: APP_DEBUG, APP_PATH_ROOT
     *
     * @param mixed ...$data
     *
     * @return void
     */
    public static function dd(...$data): void
    {
        self::d(...$data);
        exit;
    }

    /**
     * Initiate the Error Reporting based on constant "APP_DEBUG"
     * Uses: APP_DEBUG
     *
     * @return void
     */
    public static function initiateDebug(): void
    {
        if (defined('APP_DEBUG') && !empty(constant('APP_DEBUG'))) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }
    }

    /**
     * Verify that the Host is correct and if not, then redirect to correct Host.
     * Uses: APP_HOST, APP_HTTPS
     *
     * @return void
     */
    public static function forceHost(): void
    {
       // Check Host and Protocol
        $isWrongHost = defined('APP_HOST') && !empty(constant('APP_HOST')) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== constant('APP_HOST');
        $isWrongProtocol = defined('APP_HTTPS') && !empty(constant('APP_HTTPS')) && !((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || intval($_SERVER['SERVER_PORT']) === 443 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));
        if ($isWrongHost || $isWrongProtocol) {
            header('Location: http'.(defined('APP_HTTPS') && !empty(constant('APP_HTTPS')) ? 's' : '').'://'.(defined('APP_HOST') && !empty(constant('APP_HOST')) ? constant('APP_HOST') : $_SERVER['HTTP_HOST']), true, 302);
            exit;
        }
    }

    /**
     * Load Env File
     *
     * @param string $envFile - Absolute path to file.
     *
     * @return void
     */
    public static function loadEnvFile(string $envFile): void
    {
        // Check if file exists and if not, then log an error.
        if (!file_exists($envFile)) {
            error_log('SpryPHP: Missing ENV File ('.$envFile.')');

            return;
        }

        // Load Env File into env vars.
        foreach (parse_ini_file($envFile) as $envVarKey => $envVarValue) {
            putenv($envVarKey.'='.$envVarValue);
        }
    }

    /**
     * Logout of Session and abort current action with a Message.
     * Uses: APP_URI_LOGIN, APP_URI_LOGOUT
     *
     * @param string $error
     *
     * @return void
     */
    public static function abort($error): void
    {
        if ($error) {
            Alerts::addAlert('error', $error);
            if (!defined('APP_URI_LOGIN') || !defined('APP_URI_LOGOUT')) {
                error_log('SpryPHP: No APP_URI_LOGIN or APP_URI_LOGOUT defined.');
            } elseif (!in_array(Request::$path, [constant('APP_URI_LOGIN'), constant('APP_URI_LOGOUT')], true)) {
                Route::go(constant('APP_URI_LOGIN'));
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
    public static function esc($value): string
    {
        return addslashes(str_replace(['"', '&#039;'], ['&#34;', "'"], htmlspecialchars(stripslashes(strip_tags($value)), ENT_NOQUOTES, "UTF-8", false)));
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
     * Create a New Ordered UUID that is UUIDv7 compatible
     *
     * @return string
     */
    public static function newUuid(): string
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(dechex(intval(number_format(floatval(time().explode(' ', microtime())[0]), 6, '', ''))).bin2hex(random_bytes(9)), 4));
    }

    /**
     * List of States and abbreviations.
     *
     * @return array
     */
    public static function getStates()
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
