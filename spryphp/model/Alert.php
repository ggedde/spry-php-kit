<?php declare(strict_types = 1);
/**
 * This file is to handle an Alert
 */

namespace SpryPhp\Model;

use SpryPhp\Type\TypeAlert;
use SpryPhp\Provider\Functions;

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
     */
    public function __construct(string $type, string $message)
    {
        if (empty(TypeAlert::tryFrom($type))) {
            Functions::abort('Wrong Alert Type Passed. Must be one of '.implode(', ', array_column(TypeAlert::cases(), 'value')));
        }

        $this->type = $type;
        $this->message = $message;
    }
}
