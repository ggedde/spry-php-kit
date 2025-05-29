<?php declare(strict_types=1);
/**
 * Global Functions file
 */

namespace SpryPhp\Provider;

use Exception;
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
     * @param mixed  $errors
     * @param string $trace
     *
     * @uses APP_DEBUG
     * @uses APP_PATH
     *
     * @return void
     */
    public static function displayError(mixed $errors, string $trace = ''): void
    {
        echo '<div style="padding: .5em;"><div style="padding: .8em 1.6em; background-color: light-dark(#eee, #333); border: 1px solid #888; border-radius: .5em; line-height: 1.4; overflow: auto;"><pre style="white-space: pre-wrap;">';

        ob_start();
        var_dump($errors);
        $data = ob_get_contents();
        ob_end_clean();


        if (is_string($data)) {
            $data = preg_replace('/]=>\n[\ ]*/', '] => ', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/bool\((false|true)\)/', '{{bool:}} $1', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/int\(([0-9]*)\)/', '{{int:}} $1', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/float\(([0-9\.]*)\)/', '{{float:}} $1', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/string\(([0-9]*)\).*\"(.*)\"/', '{{str($1):}} $2', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/array\(([0-9]*)\).*\{/', '{{array($1):}} {', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/object\((.*)\).*\#.*{/', '{{object:}} {{{$1}}} {', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/=> NULL/', '=> {{NULL}}', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/^NULL/', '{{NULL}}', $data);
        }
        if (is_string($data)) {
            $data = preg_replace('/{\n[\ ]*}/', '{}', $data);
        }
        if (is_string($data)) {
            $data = str_replace('{{{', '<span style="color: #197239">', $data);
        }
        if (is_string($data)) {
            $data = str_replace('{{', '<span style="color: #80a0a7">', $data);
        }
        if (is_string($data)) {
            $data = str_replace(['}}}', '}}'], '</span>', $data);
        }
        if (is_string($data)) {
            $data = str_replace(' => ', '<span style="color: #888"> => </span>', $data);
        }

        if (is_string($data) && defined('APP_PATH')) {
            $data = str_replace(self::constantString('APP_PATH'), '', $data);
        }

        echo $data;

        if (!$trace) {
            ob_start();
            debug_print_backtrace(0);
            $trace = ob_get_contents();
            ob_end_clean();
        }

        if ($trace && defined('APP_DEBUG')) {
            echo "\n";
            echo '<span style="color:#794111;">';
            if (defined('APP_PATH')) {
                $trace = str_replace(self::constantString('APP_PATH'), '', $trace);
            }
            echo $trace;
            echo '</span>';
        }
        echo '</pre></div></div>';
    }

    /**
     * Basic Dump function
     *
     * @param mixed $value     Single Value
     * @param mixed ...$values Additional Values
     *
     * @return void
     */
    public static function d(mixed $value, mixed ...$values): void
    {
        if (!empty($values)) {
            $value = [
                $value,
                ...$values,
            ];
        }

        $traceArray = debug_backtrace(0, 1);

        ob_start();
        debug_print_backtrace(0);
        $trace = ob_get_contents();
        ob_end_clean();

        self::displayError(
            $value,
            '<span style="color: #006499">in '.($traceArray[0]['file'] ?? 'Unknown').':'.($traceArray[0]['line'] ?? 'Unknown')."</span>\n\n".$trace
        );
    }

    /**
     * Basic Die and Dump function
     *
     * @param mixed $value     Single Value
     * @param mixed ...$values Additional Values
     *
     * @return never
     */
    public static function dd(mixed $value, mixed ...$values): never
    {
        if (!empty($values)) {
            $value = [
                $value,
                ...$values,
            ];
        }

        $traceArray = debug_backtrace(0, 1);

        ob_start();
        debug_print_backtrace(0);
        $trace = ob_get_contents();
        ob_end_clean();

        self::displayError(
            $value,
            '<span style="color: #006499">in '.($traceArray[0]['file'] ?? 'Unknown').':'.($traceArray[0]['line'] ?? 'Unknown')."</span>\n\n".$trace
        );
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
            self::displayError(
                '<b>Uncaught Exception</b>: '.$exception->getMessage(),
                '<span style="color: #006499">in '.$exception->getFile().':'.$exception->getLine()."</span>\n\n".$exception->getTraceAsString()
            );
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
    public static function setDebug(): void
    {
        if (!defined('APP_DEBUG')) {
            throw new Exception("SpryPHP: APP_DEBUG is not defined.");
        }

        if (self::constantBool('APP_DEBUG')) {
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
     * @throws Exception
     *
     * @return void
     */
    public static function forceHost(): void
    {
        $appHost = self::constantString('APP_HOST', isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
        $appHTTPS = self::constantBool('APP_HTTPS');

        // Check Host and Protocol
        $isWrongHost = $appHost && !empty($_SERVER['HTTP_HOST']) && $appHost !== $_SERVER['HTTP_HOST'];
        $isWrongProtocol = $appHTTPS && !((!empty($_SERVER['HTTPS']) && is_string($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || (isset($_SERVER['SERVER_PORT']) && is_numeric($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443) || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));

        if ($isWrongHost || $isWrongProtocol) {
            header('Location: http'.($appHTTPS ? 's' : '').'://'.$appHost, true, 302);
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
            throw new Exception("SpryPHP: Missing ENV File ('.$envFile.')");
        }

        $data = parse_ini_file($envFile);
        if ($data) {
            // Load Env File into env vars.
            foreach ($data as $envVarKey => $envVarValue) {
                if (is_scalar($envVarValue)) {
                    putenv($envVarKey.'='.strval($envVarValue));
                }
            }
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
    public static function abort(string $error): void
    {
        if ($error) {
            Session::addAlert('error', $error);

            $appUriLogin = self::constantString('APP_URI_LOGIN');
            $appUriLogout = self::constantString('APP_URI_LOGOUT');

            if (!in_array(Request::getPath(), [$appUriLogin, $appUriLogout], true)) {
                Route::goTo($appUriLogin);
            }
        }
    }

    /**
     * Sanitize String
     *
     * @param string $string
     * @param string $space  Character for spaces.
     *
     * @return string
     */
    public static function sanitizeString(string $string, string $space = '_'): string
    {
        $string = preg_replace('/[^a-z0-9\_]/', '', trim(str_replace([' ', '-'], '_', trim(strtolower($string))), '_'));
        if (is_string($string)) {
            $string = preg_replace('/[_]{2,}/', '_', $string);
        }

        return is_string($string) ? str_replace('_', $space, $string) : '';
    }

    /**
     * Safe Value
     *
     * @param string|int|float|bool|null $value
     *
     * @return string
     */
    public static function escString(string|int|float|bool|null $value): string
    {
        return addslashes(str_replace(['"', '&#039;'], ['&#34;', "'"], htmlspecialchars(stripslashes(strip_tags(strval($value))), ENT_NOQUOTES, "UTF-8", false)));
    }

    /**
     * Convert Value for HTML Attribute
     *
     * @param string|int|float|bool|null $value
     *
     * @return string
     */
    public static function escAttr(string|int|float|bool|null $value): string
    {
        return str_replace("'", "&#39;", stripslashes(strval($value)));
    }

    /**
     * Convert to CamelCase
     *
     * @param string $string
     *
     * @return string
     */
    public static function formatCamelCase(string $string): string
    {
        $str = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', trim($string)))));

        return $str;
    }

    /**
     * Converts Keys to SnakeCase
     *
     * @param string $string
     *
     * @return string
     */
    public static function formatSnakeCase(string $string): string
    {
        $strValue = preg_replace('/(?<!^)[A-Z]/', '_$0', trim($string));

        return $strValue ? strtolower($strValue) : '';
    }

    /**
     * Returns the Singular version of the string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function formatSingular(string $string): string
    {
        if (!$string || !trim($string) || stripos(substr(trim($string), -1), 's') === false) {
            return $string;
        }

        $string = trim($string);

        if (stripos(substr($string, -3), 'ies') !== false) {
            return substr($string, 0, -3).(ctype_upper($string) ? 'Y' : 'y');
        }

        if (stripos(substr($string, -3), 'ses') !== false) {
            return substr($string, 0, -2);
        }

        if (stripos(substr($string, -1), 's') !== false) {
            return substr($string, 0, -1);
        }

        return $string;
    }

    /**
     * Returns the Plural version of the string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function formatPlural(string $string): string
    {
        if (!$string || !trim($string) || stripos(substr(trim($string), -1), 's') !== false) {
            return $string;
        }

        $string = trim($string);

        if (stripos(substr($string, -1), 'y') !== false) {
            return substr($string, 0, -1).(ctype_upper($string) ? 'IES' : 'ies');
        }

        return $string.(ctype_upper($string) ? 'S' : 's');
    }

    /**
     * Format Title
     *
     * @param string $title
     *
     * @return string
     */
    public static function formatTitle(string $title): string
    {
        return ucwords(self::sanitizeString(self::formatSnakeCase($title), ' '));
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
     * Checks the App for issues
     *
     * @throws Exception
     *
     * @return void
     */
    public static function checkAppIntegrity(): void
    {
        if (self::constantString('APP_AUTH_KEY', '__AUTH_KEY__') === '__AUTH_KEY__') {
            throw new Exception("SpryPHP: Please update APP_AUTH_KEY to a secure and unique value.");
        }

        if (self::constantString('APP_AUTH_PASSWORD', '__AUTH_PASSWORD__') === '__AUTH_PASSWORD__') {
            throw new Exception("SpryPHP: Please update APP_AUTH_PASSWORD to a secure and unique value.");
        }
    }

    /**
     * Returns the String Value of a Constant
     *
     * @param string      $constant
     * @param string|null $default
     *
     * @throws Exception
     *
     * @return string
     */
    public static function constantString(string $constant, ?string $default = null): string
    {
        if (!defined($constant) && is_null($default)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) is not defined', $constant));
        }

        $value = defined($constant) ? (constant($constant) ?: $default) : $default;

        if (!is_string($value)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) Must be a String', $constant));
        }

        return trim($value);
    }

    /**
     * Returns the Integer Value of a Constant
     *
     * @param string   $constant
     * @param int|null $default
     *
     * @throws Exception
     *
     * @return int
     */
    public static function constantInt(string $constant, ?int $default = null): int
    {
        if (!defined($constant) && is_null($default)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) is not defined', $constant));
        }

        $value = defined($constant) ? constant($constant) : $default;

        if (!is_int($value)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) Must be a Integer', $constant));
        }

        return $value;
    }

    /**
     * Returns the Float Value of a Constant
     *
     * @param string     $constant
     * @param float|null $default
     *
     * @throws Exception
     *
     * @return float
     */
    public static function constantFloat(string $constant, ?float $default = null): float
    {
        if (!defined($constant) && is_null($default)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) is not defined', $constant));
        }

        $value = defined($constant) ? constant($constant) : $default;

        if (!is_float($value)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) Must be a Float', $constant));
        }

        return $value;
    }

    /**
     * Returns the Boolean Value of a Constant
     *
     * @param string    $constant
     * @param bool|null $default
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function constantBool(string $constant, ?bool $default = null): bool
    {
        if (!defined($constant) && is_null($default)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) is not defined', $constant));
        }

        $value = defined($constant) ? constant($constant) : $default;

        if (!is_bool($value)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) Must be a Boolean', $constant));
        }

        return $value;
    }

    /**
     * Returns the Array Value of a Constant
     *
     * @param string                       $constant
     * @param array<int|string,mixed>|null $default
     *
     * @throws Exception
     *
     * @return array<int|string,mixed>
     */
    public static function constantArray(string $constant, ?array $default = null): array
    {
        if (!defined($constant) && is_null($default)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) is not defined', $constant));
        }

        $value = defined($constant) ? constant($constant) : $default;

        if (!is_array($value)) {
            throw new Exception(sprintf('SpryPHP: Constant (%s) Must be a Array', $constant));
        }

        return $value;
    }

    /**
     * Get the Contents of a Callable Function
     *
     * @param callable $func    Callable Function.
     * @param mixed    ...$args Array of Arguments to pass to the callable Function.
     *
     * @return string|null String on Success `null` on get contents failure.
     */
    public static function returnContents(callable $func, mixed ...$args): ?string
    {
        ob_start();
        call_user_func($func, ...$args);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents !== false ? $contents : null;
    }

    /**
     * List of States and abbreviations.
     *
     * @return array<string, string>
     */
    public static function getStates(): array
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
