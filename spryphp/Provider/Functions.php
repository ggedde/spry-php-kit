<?php declare(strict_types=1);
/**
 * Global Functions file
 */

namespace SpryPhp\Provider;

use Exception;
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
     * Prettify Error Messages and Stack Traces.
     *
     * @param string|array $errors
     * @param string       $trace
     *
     * @uses APP_DEBUG
     * @uses APP_PATH_ROOT
     *
     * @return void
     */
    public static function displayError(mixed $errors, string $trace = ''): void
    {
        echo '<div style="padding: .5em;"><div style="padding: .8em 1.6em; background-color: light-dark(#eee, #333); border: 1px solid #888; border-radius: .5em; line-height: 1.4; overflow: auto;"><pre style="white-space: pre-line;">';
        if ($errors) {
            if (defined('APP_PATH_ROOT')) {
                print_r(json_decode(str_replace(constant('APP_PATH_ROOT'), '', json_encode($errors))));
            } else {
                print_r($errors);
            }
        }

        if (!$trace) {
            ob_start();
            debug_print_backtrace(0);
            $trace = ob_get_contents();
            ob_end_clean();
        }

        if ($trace && defined('APP_DEBUG')) {
            echo "\n";
            echo '<span style="color:#975;">';
            if (defined('APP_PATH_ROOT')) {
                echo str_replace(constant('APP_PATH_ROOT'), '', $trace);
            } else {
                echo $trace;
            }
            echo '</span>';
        }
        echo '</pre></div></div>';
    }

    /**
     * Basic Dump function
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
        debug_print_backtrace(0);
        $trace = ob_get_contents();
        ob_end_clean();

        self::displayError($data, $trace);
    }

    /**
     * Basic Die and Dump function
     *
     * @param mixed ...$data
     *
     * @return void
     */
    public static function dd(...$data): void
    {
        if (!empty($data) && is_array($data) && count($data) === 1) {
            $data = $data[0];
        }

        ob_start();
        debug_print_backtrace(0);
        $trace = ob_get_contents();
        ob_end_clean();

        self::displayError($data, $trace);
        exit;
    }

    /**
     * Prettify Exceptions Messages and Stack Traces.
     *
     * @return void
     */
    public static function formatExceptions(): void
    {
        set_exception_handler(function (\Throwable $exception) {
            self::displayError('<b>Uncaught Exception</b>: '.$exception->getMessage(), 'in '.$exception->getFile().':'.$exception->getLine()."\n\n".$exception->getTraceAsString());
        });
    }

    /**
     * Initiate the Error Reporting based on constant "APP_DEBUG"
     *
     * @uses APP_DEBUG
     *
     * @throws Exception
     *
     * @return void
     */
    public static function initiateDebug(): void
    {
        if (!defined('APP_DEBUG')) {
            throw new Exception("SpryPHP: APP_DEBUG is not defined.", 1);
        }

        if (!empty(constant('APP_DEBUG'))) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }
    }

    /**
     * Verify that the Host is correct and if not, then redirect to correct Host.
     *
     * @uses APP_HOST
     * @uses APP_HTTPS
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
     * @throws Exception
     *
     * @return void
     */
    public static function loadEnvFile(string $envFile): void
    {
        // Check if file exists and if not, then log an error.
        if (!file_exists($envFile)) {
            throw new Exception("SpryPHP: Missing ENV File ('.$envFile.')", 1);
        }

        // Load Env File into env vars.
        foreach (parse_ini_file($envFile) as $envVarKey => $envVarValue) {
            putenv($envVarKey.'='.$envVarValue);
        }
    }

    /**
     * Logout of Session and abort current action with a Message.
     *
     * @param string $error
     *
     * @uses APP_URI_LOGIN
     * @uses APP_URI_LOGOUT
     *
     * @throws Exception
     *
     * @return void
     */
    public static function abort($error): void
    {
        if ($error) {
            Alerts::addAlert('error', $error);
            if (!defined('APP_URI_LOGIN')) {
                throw new Exception("SpryPHP: APP_URI_LOGIN is not defined", 1);
            }

            if (!defined('APP_URI_LOGOUT')) {
                throw new Exception("SpryPHP: APP_URI_LOGOUT is not defined", 1);
            }

            if (!in_array(Request::$path, [constant('APP_URI_LOGIN'), constant('APP_URI_LOGOUT')], true)) {
                Route::goTo(constant('APP_URI_LOGIN'));
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
