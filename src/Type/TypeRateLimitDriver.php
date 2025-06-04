<?php declare(strict_types=1);
/**
 * This file is to handle TypeRateLimitDriver
 */

namespace SpryPhp\Type;

/**
 * TypeRateLimitDriver Type
 */
enum TypeRateLimitDriver: string
{
    case Db   = 'db';
    case File = 'file';
}
