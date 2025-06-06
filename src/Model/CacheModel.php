<?php declare(strict_types=1);
/**
 * This file is to handle an CacheModel
 */

namespace SpryPhp\Model;

use Exception;

/**
 * Class for managing and rendering a Single CacheModel
 */
class CacheModel
{
    /**
     * Key
     *
     * @var string $key
     */
    public string $key = '';

    /**
     * Value
     *
     * @var mixed $value
     */
    public mixed $value = '';

    /**
     * Expire Time
     *
     * @var int $expires
     */
    public int $expires = 0;

    /**
     * Construct an Alert
     *
     * @param object|string|null $data Rate Limt Object Data to create or json string. Null for new CacheModel.
     *
     * @throws Exception
     */
    public function __construct(object|string|null $data)
    {
        if (is_string($data)) {
            $data = json_decode($data);
            if (!is_object($data)) {
                throw new Exception('SpryPHP: Error Parsing CacheModel from String');
            }
        }

        if (is_object($data)) {
            if (isset($data->key) && is_string($data->key)) {
                $this->key = trim($data->key);
            }

            if (isset($data->value) && !empty($data->value)) {
                $this->value = $data->value;
            }

            if (isset($data->expires) && is_scalar($data->expires)) {
                $this->expires = intval($data->expires);
            }
        }
    }

    /**
     * Check if Cache has Expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires > 0 && $this->expires < time();
    }
}
