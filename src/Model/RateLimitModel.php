<?php declare(strict_types=1);
/**
 * This file is to handle an RateLimitModel
 */

namespace SpryPhp\Model;

use Exception;
use SpryPhp\Provider\Functions;
use SpryPhp\Provider\Session;
use SpryPhp\Type\TypeRateLimitStatus;

/**
 * Class for managing and rendering a Single RateLimitModel
 */
class RateLimitModel
{
    /**
     * Current Number of Attempts
     *
     * @var int $attempts
     */
    public int $attempts = 0;

    /**
     * IP Address
     *
     * @var string $ip
     */
    public string $ip = '';

    /**
     * HTTP Method
     *
     * @var string $method
     */
    public string $method = '';

    /**
     * Key Name
     *
     * @var string $name
     */
    public string $name = '';

    /**
     * Requested Path
     *
     * @var string $path
     */
    public string $path = '';

    /**
     * User ID if we have it as a String.
     *
     * @var string $userId
     */
    public string $userId = '';

    /**
     * Time to Rest
     *
     * @var int $reset
     */
    public int $reset = 0;

    /**
     * Current Status of Requestor
     *
     * @var TypeRateLimitStatus $status
     */
    public TypeRateLimitStatus $status = TypeRateLimitStatus::Active;

    /**
     * Construct an Alert
     *
     * @param object|string|null $data Rate Limt Object Data to create or json string. Null for new RateLimitModel.
     *
     * @throws Exception
     */
    public function __construct(object|string|null $data)
    {
        if (is_string($data)) {
            $data = json_decode($data);
            if (!is_object($data)) {
                throw new Exception('SpryPHP: Error Parsing RateLimit Details from String');
            }
        }

        if (is_object($data)) {
            if (isset($data->status) && is_string($data->status)) {
                if (!TypeRateLimitStatus::tryFrom($data->status)) {
                    throw new Exception(sprintf('SpryPHP: Wrong TypeRateStatus Type Passed. Must be one of: %s', implode(', ', array_column(TypeRateLimitStatus::cases(), 'value'))));
                }
                $this->status = TypeRateLimitStatus::tryFrom($data->status);
            }

            if (isset($data->name) && is_string($data->name)) {
                $this->name = trim($data->name);
            }

            if (isset($data->attempts) && is_scalar($data->attempts)) {
                $this->attempts = intval($data->attempts);
            }

            if (isset($data->ip) && is_scalar($data->ip)) {
                $this->ip = strval($data->ip);
            }

            if (isset($data->reset) && is_scalar($data->reset)) {
                $this->reset = intval($data->reset);
            }

            if (isset($data->userId) && is_scalar($data->userId)) {
                $this->userId = trim(strval($data->userId));
            }

            if (isset($data->path) && is_string($data->path)) {
                $this->path = trim($data->path);
            }

            if (isset($data->method) && is_string($data->method)) {
                $this->method = trim($data->method);
            }
        }

        if (!$this->ip) {
            $this->ip = Functions::getIp();
        }

        if (!$this->path) {
            if (!empty($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
                $this->path = $_SERVER['REQUEST_URI'];
            }
        }

        if (!$this->method) {
            if (!empty($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])) {
                $this->method = $_SERVER['REQUEST_METHOD'];
            }
        }

        if (!$this->userId && ($user = Session::getUser())) {
            if (isset($user->id) && is_scalar($user->id)) {
                $this->userId = trim(strval($user->id));
            }
        }
    }
}
