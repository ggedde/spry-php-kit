<?php declare(strict_types=1);
/**
 * This file is to handle The Validator
 */

namespace SpryPhp\Model;

use Exception;
use SpryPhp\Provider\Functions;

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
     * Only Valid Params
     *
     * @var object $validParams
     */
    private object $validParams;

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
     * Params to Validate
     *
     * @param array<string,string|int|float|bool|null>|object $params
     *
     * @return void
     */
    public function __construct(array|object $params)
    {
        $this->params = (object) $params;
        $this->validParams = (object) [];
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

        $this->validParams->$param = $this->params->$param;

        if (is_string($this->validParams->$param) && $this->validParams->$param === '') {
            $this->validParams->$param = null;
        }

        return $this;
    }

    /**
     * Set Param as Required.
     *
     * @param string|null $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function required(?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (empty($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is required');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if ((isset($this->validParams->$param) || is_null($this->validParams->$param)) && !in_array($param, $this->invalidParams, true)) {
            $this->validParams->$param = is_scalar($this->validParams->$param) ? floatval(preg_replace('/[^0-9\.\-]/', '', strval($this->validParams->$param))) : 0.00;
            if (!is_float($this->validParams->$param)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Float');
                $this->valid = false;
                $this->invalidParams[] = $param;
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
        if ((isset($this->validParams->$param) || is_null($this->validParams->$param)) && !in_array($param, $this->invalidParams, true)) {
            $this->validParams->$param = is_scalar($this->validParams->$param) ? intval(preg_replace('/[^0-9\-]/', '', strval($this->validParams->$param))) : 0;
            if (!is_int($this->validParams->$param)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Integer');
                $this->valid = false;
                $this->invalidParams[] = $param;
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
        if ((isset($this->validParams->$param) || is_null($this->validParams->$param)) && !in_array($param, $this->invalidParams, true)) {
            $this->validParams->$param = is_scalar($this->validParams->$param) ?  preg_replace('/[^0-9]/', '', strval($this->validParams->$param)) : 0;
            if (!is_numeric($this->validParams->$param)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Numbers');
                $this->valid = false;
                $this->invalidParams[] = $param;
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
        if (isset($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $phone = is_scalar($this->validParams->$param) ? preg_replace('/[^0-9\#]/', '', strval($this->validParams->$param)) : null;

            if (!$phone) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to Phone Number.');
                $this->valid = false;
                $this->invalidParams[] = $param;
            } else {
                $splitExtension = explode('#', $phone, 2);
                $splitCode = str_split(strrev($splitExtension[0]), 10);
                $phone = strrev($splitCode[0]);

                try {
                    $this->validParams->$param = vsprintf('(%s) %s-%s%s', str_split($phone, 3)).(!empty($splitExtension[1]) ? ' #'.$splitExtension[1] : '');
                } catch (\ValueError $e) {
                    $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to Phone Number.');
                    $this->valid = false;
                    $this->invalidParams[] = $param;
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
        if (isset($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $phone = is_scalar($this->validParams->$param) ? preg_replace('/[^0-9\#]/', '', strval($this->validParams->$param)) : null;

            if (!$phone) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to Phone Number.');
                $this->valid = false;
                $this->invalidParams[] = $param;
            } else {
                $splitExtension = explode('#', $phone, 2);
                $splitCode = str_split(strrev($splitExtension[0]), 10);
                $phone = strrev($splitCode[0]);
                $code  = strrev($splitCode[1]);

                try {
                    $this->validParams->$param = ($code ? '+'.$code.' ' : '').vsprintf('(%s) %s-%s%s', str_split($phone, 3)).(!empty($splitExtension[1]) ? ' #'.$splitExtension[1] : '');
                } catch (\ValueError $e) {
                    $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is invalid and could not be converted to International Phone Number.');
                    $this->valid = false;
                    $this->invalidParams[] = $param;
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
        if ((isset($this->validParams->$param) || is_null($this->validParams->$param)) && !in_array($param, $this->invalidParams, true)) {
            $this->validParams->$param = is_scalar($this->validParams->$param) ? strval($this->validParams->$param) : '';
            if (!is_string($this->validParams->$param)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to String');
                $this->valid = false;
                $this->invalidParams[] = $param;
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
        if ((isset($this->validParams->$param) || is_null($this->validParams->$param)) && !in_array($param, $this->invalidParams, true)) {
            $this->validParams->$param = filter_var($this->validParams->$param, FILTER_VALIDATE_BOOLEAN);
            if ((is_array($this->validParams->$param) || is_object($this->validParams->$param)) && !empty($this->validParams->$param)) {
                $this->validParams->$param = true;
            }
            if ((is_array($this->validParams->$param) || is_object($this->validParams->$param)) && empty($this->validParams->$param)) {
                $this->validParams->$param = false;
            }

            if (!is_bool($this->validParams->$param)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to Boolean');
                $this->valid = false;
                $this->invalidParams[] = $param;
            }
        }

        return $this;
    }

    /**
     * Convert to Date Value
     *
     * @param string      $format       Option to set Date Format.
     * @param string|null $offset       Ex -7 hours | +2 minutes etc
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
        if ((isset($this->validParams->$param) || is_null($this->validParams->$param)) && !in_array($param, $this->invalidParams, true)) {
            if (is_null($this->validParams->$param)) {
                $dateTime = strtotime(Functions::constantString('APP_DATETIME_TIME'));
            } elseif (is_numeric($this->validParams->$param)) {
                $dateTime = intval($this->validParams->$param);
            } elseif (is_string($this->validParams->$param)) {
                $dateTime = strtotime($this->validParams->$param);
            } else {
                $dateTime = null;
            }

            if ($offset) {
                $dateTime = strtotime($offset, $dateTime ?: null);
            }

            $this->validParams->$param = gmdate($format, $dateTime ?: null);

            if (empty($this->validParams->$param) || !is_string($this->validParams->$param)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') could not be converted to DateTime');
                $this->valid = false;
                $this->invalidParams[] = $param;
            }
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
        if (!is_null($this->validParams->$param) && !is_string($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a String');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !is_int($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Integer');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !is_float($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Float');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !is_numeric($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Number');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !is_bool($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be a Boolean');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !is_array($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Array');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !is_array($this->validParams->$param) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be an Object');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $this->validParams->$param))) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Database UUID');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_EMAIL) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Email Address');
            $this->valid = false;
            $this->invalidParams[] = $param;
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

        $phone = is_scalar($this->validParams->$param) ? preg_replace('/[^0-9\#]/', '', strval($this->validParams->$param)) : null;

        if (!$phone) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Phone Number');
            $this->valid = false;
            $this->invalidParams[] = $param;
        } else {
            $phone = substr($phone, -10);
            $inValid = match ($phone) {
                '1234567890' => true,
                '1231231234' => true,
                '5555555555' => true,
                '0000000000' => true,
                default => false,
            };

            if (($inValid || strlen($phone) < 10) && !in_array($param, $this->invalidParams, true)) {
                $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Phone Number');
                $this->valid = false;
                $this->invalidParams[] = $param;
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
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_IP) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid IP Address');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_URL) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid URL Address');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Domain Address');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && $this->validParams->$param !== $value && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not the same as '.(is_string($value) || is_numeric($value) || is_bool($value) ? strval($value) : ' required value'));
            $this->valid = false;
            $this->invalidParams[] = $param;
        }

        return $this;
    }

    /**
     * Param must be one of the array items
     *
     * @param array<int|string,mixed> $array
     * @param string|null             $customErrorMessage Custom Error Message
     *
     * @return Validator
     */
    public function inArray(array $array, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !in_array($this->validParams->$param, $array, true) && !in_array($param, $this->invalidParams, true)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be one of: '.implode(', ', $array));
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!empty($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && (!is_string($this->validParams->$param) || strlen($this->validParams->$param) < $length)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be at least '.$length.' characters long');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!empty($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && (!is_string($this->validParams->$param) || strlen($this->validParams->$param) > $length)) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be at least '.$length.' characters long');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!empty($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && !is_int($this->validParams->$param) && !is_float($this->validParams->$param) && $this->validParams->$param < $value) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be greater than or equal to '.$value);
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!empty($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && !is_int($this->validParams->$param) && !is_float($this->validParams->$param) && $this->validParams->$param > $value) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') must be less than or equal to '.$value);
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->validParams->$param))) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Date');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && is_string($this->validParams->$param) &&
            (
                preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param)
                || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param)
                || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}\:[0-9]{2}$/', $this->validParams->$param)
            )
        ) {
            $this->validParams->$param = gmdate('Y-m-d H:i:s', strtotime($this->validParams->$param) ?: null);
        }
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param))) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Date');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param))) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') is not a valid Date');
            $this->valid = false;
            $this->invalidParams[] = $param;
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
    public function lessThan(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && $this->validParams->$param >= $value) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be less than '.$value);
            $this->valid = false;
            $this->invalidParams[] = $param;
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
    public function lessThanOrEqualTo(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && $this->validParams->$param !== $value && $this->validParams->$param > $value) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be less than or equal to '.$value);
            $this->valid = false;
            $this->invalidParams[] = $param;
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
    public function greaterThan(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && $this->validParams->$param <= $value) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be greater than '.$value);
            $this->valid = false;
            $this->invalidParams[] = $param;
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
    public function greaterThanOrEqualTo(int|float|string $value, ?string $customErrorMessage = null): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !in_array($param, $this->invalidParams, true) && $this->validParams->$param !== $value && $this->validParams->$param < $value) {
            $this->addError($customErrorMessage ? $customErrorMessage : 'Parameter ('.$this->paramLabel.') Must be greater than or equal to '.$value);
            $this->valid = false;
            $this->invalidParams[] = $param;
        }

        return $this;
    }

    /**
     * Returns all the Valid Params or 'null' if one of the fields failed validation.
     *
     * @return object|null null on failure, valid params on success
     */
    public function getValidParams(): ?object
    {
        return $this->valid ? $this->validParams : null;
    }

    /**
     * Returns all the Invalid Param Names as array
     *
     * @return string[]
     */
    public function getInvalidParams(): array
    {
        return $this->invalidParams;
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
     * @param string $error Message of Error.
     *
     * @return void
     */
    private function addError(string $error): void
    {
        if (!empty($error)) {
            $this->errors[] = $error;
        }
    }
}
