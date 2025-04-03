<?php declare(strict_types = 1);
/**
 * This file is to handle The Validator
 */

namespace SpryPhp\Model;

use Exception;
use SpryPhp\Provider\Alerts;

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
     * @param array|object $params
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
     * @param string $param      - Name of parameter to start checking against.
     * @param string $paramLabel - Label of parameter to start checking against.
     * @param string $default    - Default Value if param is not set.
     *
     * @return Validator
     */
    public function param(string $param, string $paramLabel = '', mixed $default = null)
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
     * @return Validator
     */
    public function required(): Validator
    {
        $param = $this->param;
        if (empty($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is required');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Convert to Float Value
     *
     * @return Validator
     */
    public function convertToFloat(): Validator
    {
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = floatval($this->validParams->$param);
        }

        return $this;
    }

    /**
     * Convert to Int Value
     *
     * @return Validator
     */
    public function convertToInt(): Validator
    {
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = intval($this->validParams->$param);
        }

        return $this;
    }

    /**
     * Convert to String Value
     *
     * @return Validator
     */
    public function convertToString(): Validator
    {
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = strval($this->validParams->$param);
        }

        return $this;
    }

    /**
     * Convert to Bool Value
     *
     * @return Validator
     */
    public function convertToBool(): Validator
    {
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            if (is_string($this->validParams->$param) && in_array(strtolower($this->validParams->$param), ['1', 'yes', 'true'])) {
                $this->validParams->$param = true;
            }
            if (is_string($this->validParams->$param) && in_array(strtolower($this->validParams->$param), ['0', 'no', 'false'])) {
                $this->validParams->$param = false;
            }
            if (is_numeric($this->validParams->$param) && floatval($this->validParams->$param) > 0) {
                $this->validParams->$param = true;
            }
            if (is_numeric($this->validParams->$param) && floatval($this->validParams->$param) === 0) {
                $this->validParams->$param = false;
            }
            if ((is_array($this->validParams->$param) || is_object($this->validParams->$param)) && !empty($this->validParams->$param)) {
                $this->validParams->$param = true;
            }
            if ((is_array($this->validParams->$param) || is_object($this->validParams->$param)) && empty($this->validParams->$param)) {
                $this->validParams->$param = false;
            }
            $this->validParams->$param = boolval($this->validParams->$param);
        }

        return $this;
    }

    /**
     * Convert to String Value
     *
     * @throws Exception
     *
     * @return Validator
     */
    public function convertToDate(): Validator
    {
        if (!defined('APP_TIME')) {
            throw new Exception("SpryPHP: APP_TIME is not defined.", 1);
        }
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = gmdate('Y-m-d', is_null($this->validParams->$param) ? constant('APP_TIME') : strtotime($this->validParams->$param));
        }

        return $this;
    }

    /**
     * Convert to String Value
     *
     * @throws Exception
     *
     * @return Validator
     */
    public function convertToDateTime(): Validator
    {
        if (!defined('APP_TIME')) {
            throw new Exception("SpryPHP: APP_TIME is not defined.", 1);
        }
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = gmdate('Y-m-d H:i:s', is_null($this->validParams->$param) ? constant('APP_TIME') : strtotime($this->validParams->$param));
        }

        return $this;
    }

    /**
     * Convert Date Offset by hours
     *
     * @param string $offset - Ex (-7 hours, +2 minutes), etc
     *
     * @return Validator
     */
    public function convertDateOffset(string $offset): Validator
    {
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = gmdate('Y-m-d', strtotime($offset, $this->validParams->$param));
        }

        return $this;
    }

    /**
     * Convert Date Offset by hours
     *
     * @param string $offset - Ex (-7 hours, +2 minutes), etc
     *
     * @return Validator
     */
    public function convertDateTimeOffset(string $offset): Validator
    {
        $param = $this->param;
        if (isset($this->validParams->$param) || is_null($this->validParams->$param)) {
            $this->validParams->$param = gmdate('Y-m-d H:i:s', strtotime($offset, $this->validParams->$param));
        }

        return $this;
    }

    /**
     * Param must be a valid string
     *
     * @return Validator
     */
    public function isString(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_string($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be a String');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid int
     *
     * @return Validator
     */
    public function isInt(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_int($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be an Integer');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid float
     *
     * @return Validator
     */
    public function isFloat(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_float($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be a Float');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid int
     *
     * @return Validator
     */
    public function isNumber(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_numeric($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be a Number');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid boolean
     *
     * @return Validator
     */
    public function isBool(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_bool($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be a Boolean');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid array
     *
     * @return Validator
     */
    public function isArray(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_array($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be an Array');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid object
     *
     * @return Validator
     */
    public function isObject(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !is_array($this->validParams->$param)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be an Object');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid UUID
     *
     * @return Validator
     */
    public function isUuid(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $this->validParams->$param))) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid Database UUID');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid Email
     *
     * @return Validator
     */
    public function isEmail(): Validator
    {
        $param = $this->param;
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_EMAIL)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid Email Address');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid IP
     *
     * @return Validator
     */
    public function isIp(): Validator
    {
        $param = $this->param;
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_IP)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid IP Address');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid URL
     *
     * @return Validator
     */
    public function isUrl(): Validator
    {
        $param = $this->param;
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_URL)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid URL Address');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be a valid Domain
     *
     * @return Validator
     */
    public function isDomain(): Validator
    {
        $param = $this->param;
        if (!filter_var($this->validParams->$param, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid Domain Address');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be equal to
     *
     * @param mixed $value
     *
     * @return Validator
     */
    public function is(mixed $value): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && $this->validParams->$param !== $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not the same as '.(is_string($value) || is_numeric($value) || is_bool($value) ? strval($value) : ' required value'));
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Param must be one of the array items
     *
     * @param array $array
     *
     * @return Validator
     */
    public function inArray(array $array): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && !in_array($this->validParams->$param, $array, true)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be one of: '.implode(', ', $array));
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a minimum Length.
     *
     * @param int $length
     *
     * @return Validator
     */
    public function minLength(int $length): Validator
    {
        $param = $this->param;
        if (!empty($this->validParams->$param) && (!is_string($this->validParams->$param) || strlen($this->validParams->$param) < $length)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be at least '.$length.' characters long');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a maximum Length.
     *
     * @param int $length
     *
     * @return Validator
     */
    public function maxLength(int $length): Validator
    {
        $param = $this->param;
        if (!empty($this->validParams->$param) && (!is_string($this->validParams->$param) || strlen($this->validParams->$param) > $length)) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be at least '.$length.' characters long');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a minimum value.
     *
     * @param float $value
     *
     * @return Validator
     */
    public function min(float $value): Validator
    {
        $param = $this->param;
        if (!empty($this->validParams->$param) && !is_int($this->validParams->$param) && !is_float($this->validParams->$param) && $this->validParams->$param < $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be greater than or equal to '.$value);
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a maximum value.
     *
     * @param float $value
     *
     * @return Validator
     */
    public function max(float $value): Validator
    {
        $param = $this->param;
        if (!empty($this->validParams->$param) && !is_int($this->validParams->$param) && !is_float($this->validParams->$param) && $this->validParams->$param > $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') must be less than or equal to '.$value);
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a valid date format.
     *
     * @return Validator
     */
    public function isDate(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->validParams->$param))) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid Date');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a valid date format.
     *
     * @return Validator
     */
    public function isDateTime(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && is_string($this->validParams->$param) && (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param)
         || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param)
         || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}\:[0-9]{2}$/', $this->validParams->$param))) {
            $this->validParams->$param = gmdate('Y-m-d H:i:s', strtotime($this->validParams->$param));
        }
        if (!is_null($this->validParams->$param) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param))) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid Date');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to have a valid date format.
     *
     * @return Validator
     */
    public function isTime(): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && (!is_string($this->validParams->$param) || !preg_match('/^[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $this->validParams->$param))) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') is not a valid Date');
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to be less then.
     *
     * @param int|float|string $value
     *
     * @return Validator
     */
    public function lessThan(int|float|string $value): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && $this->validParams->$param >= $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') Must be less than '.$value);
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to be less then or equal to.
     *
     * @param int|float|string $value
     *
     * @return Validator
     */
    public function lessThanOrEqualTo(int|float|string $value): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && $this->validParams->$param !== $value && $this->validParams->$param > $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') Must be less than or equal to '.$value);
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to be less then.
     *
     * @param int|float|string $value
     *
     * @return Validator
     */
    public function greaterThan(int|float|string $value): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && $this->validParams->$param <= $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') Must be greater than '.$value);
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Require Param to be less then or equal to.
     *
     * @param int|float|string $value
     *
     * @return Validator
     */
    public function greaterThanOrEqualTo(int|float|string $value): Validator
    {
        $param = $this->param;
        if (!is_null($this->validParams->$param) && $this->validParams->$param !== $value && $this->validParams->$param < $value) {
            Alerts::addAlert('error', 'Parameter ('.$this->paramLabel.') Must be greater than or equal to '.$value);
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Returns all the Valid Params or 'null' if one of the fields failed validation.
     *
     * @return object|null null on failure, valid params on success
     */
    public function valid(): ?object
    {
        return $this->valid ? $this->validParams : null;
    }
}
