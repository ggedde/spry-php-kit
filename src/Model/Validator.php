<?php declare(strict_types=1);
/**
 * This file is to handle The Validator
 */

namespace SpryPhp\Model;

use SpryPhp\Provider\Functions;
use Exception;
use stdClass;

/**
 * Class for Validator
 */
class Validator
{
    /**
     * Params
     *
     * @var object $params
     */
    private object $params;

    /**
     * All Requested Params
     *
     * @var object $requestedParams
     */
    private object $requestedParams;

    /**
     * Store InValid Params
     *
     * @var string[] $invalidParams
     */
    private array $invalidParams = [];

    /**
     * Store Errors
     *
     * @var string[] $errors
     */
    private array $errors = [];

    /**
     * Current Param to check against
     *
     * @var string $param
     */
    private string $param;

    /**
     * Current Param Name to check against
     *
     * @var string $paramLabel
     */
    private string $paramLabel;

    /**
     * Whether current Validation is Valid or not.
     *
     * @var bool $param
     */
    private bool $valid = true;

    /**
     * Whether current Validation is Valid or not.
     *
     * @var bool $stackErrors
     */
    private bool $stackErrors = false;

    /**
     * Params to Validate
     *
     * @param array<string,mixed>|object $params
     * @param bool                       $stackErrors Default (false) is to only track one Error per Param. Stacking will Track all Errors per Param.
     *
     * @return void
     */
    public function __construct(array|object $params, bool $stackErrors = false)
    {
        $this->params = (object) $params;
        $this->requestedParams = (object) [];
        $this->stackErrors = $stackErrors;
    }

    /**
     * Sets the current Param to Validate
     *
     * @param string                                               $param      Name of parameter to start checking against.
     * @param string                                               $paramLabel Label of parameter to start checking against.
     * @param object|array<mixed,mixed>|string|float|int|bool|null $default    Default Value if param is not set.
     *
     * @return Validator
     */
    public function param(string $param, string $paramLabel = '', object|array|string|float|int|bool|null $default = null)
    {
        if (!isset($this->params->$param)) {
            $this->params->$param = $default;
        }

        $this->param = $param;
        $this->paramLabel = !empty($paramLabel) ? $paramLabel : $param;

        $this->requestedParams->$param = $this->params->$param;

        if (is_string($this->requestedParams->$param) && $this->requestedParams->$param === '') {
            $this->requestedParams->$param = null;
        }

        return $this;
    }

    /**
     * Param must be equal to
     *
     * @param mixed       $value
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function is(mixed $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && $this->requestedParams->$param !== $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not the same as '.(is_string($value) || is_numeric($value) || is_bool($value) ? strval($value) : ' required value'));
        }

        return $this;
    }

    /**
     * Param is Required.
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isRequired(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (empty($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is required');
        }

        return $this;
    }

    /**
     * Param must be a valid string
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isString(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_string($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a String');
        }

        return $this;
    }

    /**
     * Param must be a valid int
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isInt(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_int($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Integer');
        }

        return $this;
    }

    /**
     * Param must be a valid float
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isFloat(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_float($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Float');
        }

        return $this;
    }

    /**
     * Param must be a valid int
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isNumber(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_numeric($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Number');
        }

        return $this;
    }

    /**
     * Param must be a valid boolean
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isBool(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_bool($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Boolean');
        }

        return $this;
    }

    /**
     * Param must be a valid array
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isArray(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_array($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Array');
        }

        return $this;
    }

    /**
     * Param must be a valid object
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isObject(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !is_array($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Object');
        }

        return $this;
    }

    /**
     * Param must be a valid UUID
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isUuid(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && (!is_string($this->requestedParams->$param) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $this->requestedParams->$param))) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Database UUID');
        }

        return $this;
    }

    /**
     * Param must be a valid Email
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isEmail(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!filter_var($this->requestedParams->$param, FILTER_VALIDATE_EMAIL)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Email Address');
        }

        return $this;
    }

    /**
     * Param must be a valid Phone Number
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isPhone(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        $phone = is_scalar($this->requestedParams->$param) ? preg_replace('/[^0-9\#]/', '', strval($this->requestedParams->$param)) : null;
        if (!$phone) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Phone Number');
        } else {
            $phone = substr($phone, -10);
            $inValid = match ($phone) {
                '1234567890' => true,
                '1231231234' => true,
                '5555555555' => true,
                '0000000000' => true,
                default => false,
            };

            if (($inValid || strlen($phone) < 10)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Phone Number');
            }
        }

        return $this;
    }

    /**
     * Param must be a valid IP
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isIp(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!filter_var($this->requestedParams->$param, FILTER_VALIDATE_IP)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid IP Address');
        }

        return $this;
    }

    /**
     * Param must be a valid URL
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isUrl(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!filter_var($this->requestedParams->$param, FILTER_VALIDATE_URL)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid URL Address');
        }

        return $this;
    }

    /**
     * Param must be a valid Domain
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isDomain(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!filter_var($this->requestedParams->$param, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Domain Address');
        }

        return $this;
    }

    /**
     * Require Param to have a valid date format.
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isDate(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && (!is_string($this->requestedParams->$param) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->requestedParams->$param))) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Date');
        }

        return $this;
    }

    /**
     * Require Param to have a valid date format.
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isDateTime(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && is_string($this->requestedParams->$param) &&
            (
                preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->requestedParams->$param)
                || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}\:[0-9]{2}$/', $this->requestedParams->$param)
                || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}\:[0-9]{2}$/', $this->requestedParams->$param)
            )
        ) {
            $this->requestedParams->$param = gmdate('Y-m-d H:i:s', strtotime($this->requestedParams->$param) ?: null);
        }
        if (!is_null($this->requestedParams->$param) && (!is_string($this->requestedParams->$param) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->requestedParams->$param))) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Date');
        }

        return $this;
    }

    /**
     * Require Param to have a valid date format.
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isTime(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && (!is_string($this->requestedParams->$param) || !preg_match('/^[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->requestedParams->$param))) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Date');
        }

        return $this;
    }

    /**
     * Require Param to be less then.
     *
     * @param int|float|string $value
     * @param string|null      $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isLessThan(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && $this->requestedParams->$param >= $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be less than '.$value);
        }

        return $this;
    }

    /**
     * Require Param to be less then or equal to.
     *
     * @param int|float|string $value
     * @param string|null      $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isLessThanOrEqualTo(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && $this->requestedParams->$param !== $value && $this->requestedParams->$param > $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be less than or equal to '.$value);
        }

        return $this;
    }

    /**
     * Require Param to be less then.
     *
     * @param int|float|string $value
     * @param string|null      $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isGreaterThan(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && $this->requestedParams->$param <= $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be greater than '.$value);
        }

        return $this;
    }

    /**
     * Require Param to be less then or equal to.
     *
     * @param int|float|string $value
     * @param string|null      $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isGreaterThanOrEqualTo(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && $this->requestedParams->$param !== $value && $this->requestedParams->$param < $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be greater than or equal to '.$value);
        }

        return $this;
    }

    /**
     * Param must be in String
     *
     * @param string      $string
     * @param boolean     $ignoreCase
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isInString(string $string, bool $ignoreCase = false, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param)) {
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a string');

                return $this;
            }
            if (($ignoreCase && stripos($string, $this->requestedParams->$param) === false) || (!$ignoreCase && strpos($string, $this->requestedParams->$param) === false)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be in: '.$string);
            }
        }

        return $this;
    }

    /**
     * Param must Not be in String
     *
     * @param string      $string
     * @param boolean     $ignoreCase
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isNotInString(string $string, bool $ignoreCase = false, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param)) {
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a string');

                return $this;
            }
            if (($ignoreCase && stripos($string, $this->requestedParams->$param) !== false) || (!$ignoreCase && strpos($string, $this->requestedParams->$param) !== false)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be in: '.$string);
            }
        }

        return $this;
    }

    /**
     * Param must be one of the array items
     *
     * @param array<int|mixed> $array
     * @param bool             $strictComparison
     * @param string|null      $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isInArray(array $array, $strictComparison = true, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && !in_array($this->requestedParams->$param, $array, $strictComparison)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be one of: '.implode(', ', $array));
        }

        return $this;
    }

    /**
     * Param must Not be one of the array items
     *
     * @param array<int|mixed> $array
     * @param bool             $strictComparison
     * @param string|null      $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function isNotInArray(array $array, $strictComparison = true, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param) && in_array($this->requestedParams->$param, $array, $strictComparison)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be one of: '.implode(', ', $array));
        }

        return $this;
    }

    /**
     * Param must contain a specific string and Requires Param to be a String
     *
     * @param string      $string
     * @param boolean     $ignoreCase
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function contains(string|int|float $string, bool $ignoreCase = false, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param)) {
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a string');

                return $this;
            }
            if (($ignoreCase && stripos($this->requestedParams->$param, strval($string)) === false) || (!$ignoreCase && strpos($this->requestedParams->$param, strval($string)) === false)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must contain: '.$string);
            }
        }

        return $this;
    }

    /**
     * Param must Not contain a specific string and Requires Param to be a String
     *
     * @param string      $string
     * @param boolean     $ignoreCase
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function notContains(string|int|float $string, bool $ignoreCase = false, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param)) {
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a string');

                return $this;
            }
            if (($ignoreCase && stripos($this->requestedParams->$param, strval($string)) !== false) || (!$ignoreCase && strpos($this->requestedParams->$param, strval($string)) !== false)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must not contain: '.$string);
            }
        }

        return $this;
    }

    /**
     * Param must match the Regex Expression and Requires Param to be a String
     *
     * @param string|string[]      $regex              Single Regex or Multiple
     * @param string|string[]|null $customErrorMessage Custom Error Message - Single or Multiple that match the $regex array
     *
     * @return Validator
     */
    public function matches(string|array $regex, string|array|null $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param)) {
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, is_string($customErrorMessage) ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a string');

                return $this;
            }
            if (is_array($regex)) {
                foreach ($regex as $index => $reg) {
                    if (!is_string($this->requestedParams->$param) || !preg_match($reg, $this->requestedParams->$param)) {
                        $this->addError($param, is_array($customErrorMessage) && isset($customErrorMessage[$index]) ? $customErrorMessage[$index] : (is_string($customErrorMessage) ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must match the Regular Expression'));
                    }
                }
            } else {
                if (!preg_match($regex, $this->requestedParams->$param)) {
                    $this->addError($param, is_string($customErrorMessage) ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must match the Regular Expression');
                }
            }
        }

        return $this;
    }

    /**
     * Param must Not match the Regex Expression and Requires Param to be a String
     *
     * @param string      $regex
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function notMatches(string $regex, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!is_null($this->requestedParams->$param)) {
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a string');

                return $this;
            }
            if (preg_match($regex, $this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must match the Regular Expression');
            }
        }

        return $this;
    }

    /**
     * Require Param to have a minimum value.
     *s
     * @param float       $value
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function min(float $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!empty($this->requestedParams->$param) && !is_int($this->requestedParams->$param) && !is_float($this->requestedParams->$param) && $this->requestedParams->$param < $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be greater than or equal to '.$value);
        }

        return $this;
    }

    /**
     * Require Param to have a minimum Length.
     *
     * @param int         $length
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function minLength(int $length, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!empty($this->requestedParams->$param) && (!is_string($this->requestedParams->$param) || strlen($this->requestedParams->$param) < $length)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be at least '.$length.' characters long');
        }

        return $this;
    }

    /**
     * Require Param to have a maximum Length.
     *
     * @param int         $length
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function maxLength(int $length, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!empty($this->requestedParams->$param) && (!is_string($this->requestedParams->$param) || strlen($this->requestedParams->$param) > $length)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be at least '.$length.' characters long');
        }

        return $this;
    }

    /**
     * Require Param to have a maximum value.
     *
     * @param float       $value
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function max(float $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (!empty($this->requestedParams->$param) && !is_int($this->requestedParams->$param) && !is_float($this->requestedParams->$param) && $this->requestedParams->$param > $value) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be less than or equal to '.$value);
        }

        return $this;
    }

    /**
     * Convert to Float Value
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToFloat(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if ((isset($this->requestedParams->$param) || is_null($this->requestedParams->$param))) {
            $this->requestedParams->$param = is_scalar($this->requestedParams->$param) ? floatval(preg_replace('/[^0-9\.\-]/', '', strval($this->requestedParams->$param))) : 0.00;
            if (!is_float($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Float');
            }
        }

        return $this;
    }

    /**
     * Convert to Int Value
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToInt(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if ((isset($this->requestedParams->$param) || is_null($this->requestedParams->$param))) {
            $this->requestedParams->$param = is_scalar($this->requestedParams->$param) ? intval(preg_replace('/[^0-9\-]/', '', strval($this->requestedParams->$param))) : 0;
            if (!is_int($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Integer');
            }
        }

        return $this;
    }

    /**
     * Convert to Numbers Only. Keeps it as a String.
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToNumbers(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if ((isset($this->requestedParams->$param) || is_null($this->requestedParams->$param))) {
            $this->requestedParams->$param = is_scalar($this->requestedParams->$param) ?  preg_replace('/[^0-9]/', '', strval($this->requestedParams->$param)) : 0;
            if (!is_numeric($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Numbers');
            }
        }

        return $this;
    }

    /**
     * Convert to Phone
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToPhone(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (isset($this->requestedParams->$param)) {
            $phone = is_scalar($this->requestedParams->$param) ? preg_replace('/[^0-9\#]/', '', strval($this->requestedParams->$param)) : null;

            if (!$phone) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to Phone Number.');
            } else {
                $splitExtension = explode('#', $phone, 2);
                $splitCode = str_split(strrev($splitExtension[0]), 10);
                $phone = strrev($splitCode[0]);

                try {
                    $this->requestedParams->$param = vsprintf('(%s) %s-%s%s', str_split($phone, 3)).(!empty($splitExtension[1]) ? ' #'.$splitExtension[1] : '');
                } catch (\ValueError $e) {
                    $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to Phone Number.');
                }
            }
        }

        return $this;
    }

    /**
     * Convert to Phone
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToPhoneInternational(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if (isset($this->requestedParams->$param)) {
            $phone = is_scalar($this->requestedParams->$param) ? preg_replace('/[^0-9\#]/', '', strval($this->requestedParams->$param)) : null;

            if (!$phone) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to Phone Number.');
            } else {
                $splitExtension = explode('#', $phone, 2);
                $splitCode = str_split(strrev($splitExtension[0]), 10);
                $phone = strrev($splitCode[0]);
                $code  = strrev($splitCode[1]);

                try {
                    $this->requestedParams->$param = ($code ? '+'.$code.' ' : '').vsprintf('(%s) %s-%s%s', str_split($phone, 3)).(!empty($splitExtension[1]) ? ' #'.$splitExtension[1] : '');
                } catch (\ValueError $e) {
                    $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to International Phone Number.');
                }
            }
        }

        return $this;
    }

    /**
     * Convert to String Value
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToString(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if ((isset($this->requestedParams->$param) || is_null($this->requestedParams->$param))) {
            $this->requestedParams->$param = is_scalar($this->requestedParams->$param) ? strval($this->requestedParams->$param) : '';
            if (!is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to String');
            }
        }

        return $this;
    }

    /**
     * Convert to Bool Value
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function convertToBool(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if ((isset($this->requestedParams->$param) || is_null($this->requestedParams->$param))) {
            $this->requestedParams->$param = filter_var($this->requestedParams->$param, FILTER_VALIDATE_BOOLEAN);
            if ((is_array($this->requestedParams->$param) || is_object($this->requestedParams->$param)) && !empty($this->requestedParams->$param)) {
                $this->requestedParams->$param = true;
            }
            if ((is_array($this->requestedParams->$param) || is_object($this->requestedParams->$param)) && empty($this->requestedParams->$param)) {
                $this->requestedParams->$param = false;
            }

            if (!is_bool($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Boolean');
            }
        }

        return $this;
    }

    /**
     * Convert to Date Value
     *
     * @param string      $format             Option to set Date Format.
     * @param string|null $offset             Ex -7 hours | +2 minutes etc
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @throws Exception
     *
     * @uses APP_DATETIME_TIME
     *
     * @return Validator
     */
    public function convertToDateTime(string $format = 'Y-m-d H:i:s', ?string $offset = null, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return $this;
        }

        if ((isset($this->requestedParams->$param) || is_null($this->requestedParams->$param))) {
            if (is_null($this->requestedParams->$param)) {
                $dateTime = strtotime(Functions::constantString('APP_DATETIME_TIME'));
            } elseif (is_numeric($this->requestedParams->$param)) {
                $dateTime = intval($this->requestedParams->$param);
            } elseif (is_string($this->requestedParams->$param)) {
                $dateTime = strtotime($this->requestedParams->$param);
            } else {
                $dateTime = null;
            }

            if ($offset) {
                $dateTime = strtotime($offset, $dateTime ?: null);
            }

            $this->requestedParams->$param = gmdate($format, $dateTime ?: null);

            if (empty($this->requestedParams->$param) || !is_string($this->requestedParams->$param)) {
                $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to DateTime');
            }
        }

        return $this;
    }

    /**
     * Returns all the Valid Params or 'null' if one of the fields failed validation.
     *
     * @param bool $returnAny Return any that have passed even if others have failed. Default is false.
     *
     * @return object|null null on failure, valid params on success
     */
    public function getValidParams(bool $returnAny = false): ?object
    {
        if (!$returnAny && !$this->isValid()) {
            return null;
        }

        $validParams = [];
        foreach ((array) $this->requestedParams as $key => $value) {
            if (!in_array($key, $this->invalidParams, true)) {
                $validParams[$key] = $value;
            }
        }

        return $validParams ? (object) $validParams : null;
    }

    /**
     * Returns all the Invalid Param Names as array
     *
     * @return object|null null on failure, invalid params on success
     */
    public function getInvalidParams(): ?object
    {
        $invalidParams = [];
        foreach ((array) $this->requestedParams as $key => $value) {
            if (in_array($key, $this->invalidParams, true)) {
                $invalidParams[$key] = $value;
            }
        }

        return $invalidParams ? (object) $invalidParams : null;
    }

    /**
     * Returns Param as String
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return string
     */
    public function getString(?string $customErrorMessage = null): string
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return '';
        }

        if (!is_string($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a String.');

            return '';
        }

        return strval($this->requestedParams->$param);
    }

    /**
     * Returns Param as Int
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return int
     */
    public function getInt(?string $customErrorMessage = null): int
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return 0;
        }

        if (!is_int($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Integer.');

            return 0;
        }

        return intval($this->requestedParams->$param);
    }

    /**
     * Returns Param as Float
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return float
     */
    public function getFloat(?string $customErrorMessage = null): float
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return 0;
        }

        if (!is_float($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Float.');

            return 0;
        }

        return floatval($this->requestedParams->$param);
    }

    /**
     * Returns Param as Array
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return array<int|string,mixed>
     */
    public function getArray(?string $customErrorMessage = null): array
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return [];
        }

        if (!is_array($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Array.');

            return [];
        }

        return (array) $this->requestedParams->$param;
    }

    /**
     * Returns Param as Object
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return object
     */
    public function getObject(?string $customErrorMessage = null): object
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return new stdClass();
        }

        if (!is_object($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Object.');

            return new stdClass();
        }

        return (object) $this->requestedParams->$param;
    }

    /**
     * Returns Param as Boolean
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return bool
     */
    public function getBool(?string $customErrorMessage = null): bool
    {
        $param = $this->param;
        if (!$this->stackErrors && in_array($param, $this->invalidParams, true)) {
            return false;
        }

        if (!is_bool($this->requestedParams->$param)) {
            $this->addError($param, $customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Boolean.');

            return false;
        }

        return boolval($this->requestedParams->$param);
    }

    /**
     * Returns all Errors
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns true if all is Valid or false if not.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Add Error
     *
     * @param string $param Parameter of Error.
     * @param string $error Message of Error.
     *
     * @return void
     */
    public function addError(string $param, string $error): void
    {
        if (!empty($error)) {
            $this->valid = false;
            $this->errors[] = $error;

            if (!in_array($param, $this->invalidParams, true)) {
                $this->invalidParams[] = $param;
            }
        }
    }
}
