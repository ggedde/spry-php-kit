<?php declare(strict_types = 1);
/**
 * This file is to handle Alerts
 */

namespace SpryPhp\Type;

/**
 * Alert Types
 */
enum TypeAlert: string
{
    case Error = 'error';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
}
