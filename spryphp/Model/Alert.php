<?php declare(strict_types = 1);
/**
 * This file is to handle an Alert
 */

namespace SpryPhp\Model;

use Exception;
use SpryPhp\Type\TypeAlert;

/**
 * Class for managing and rendering a Single Alert
 */
class Alert
{
    /**
     * Alert Type
     *
     * @var string $type - error | info | warning | success
     */
    public string $type = '';

    /**
     * Alert Message
     *
     * @var string $message
     */
    public string $message = '';

    /**
     * Construct an Alert
     *
     * @param string $type    - Type of Alert - error | info | success | warning
     * @param string $message - Message for Alert.
     *
     * @throws Exception
     */
    public function __construct(string $type, string $message)
    {
        if (empty(TypeAlert::tryFrom($type))) {
            throw new Exception(sprintf('SpryPHP: Wrong Alert Type Passed. Must be one of: %s', implode(', ', array_column(TypeAlert::cases(), 'value'))), 1);
        }

        $this->type = $type;
        $this->message = $message;
    }
}
