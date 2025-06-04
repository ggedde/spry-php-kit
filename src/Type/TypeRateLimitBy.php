<?php declare(strict_types=1);
/**
 * This file is to handle TypeRateLimitBy
 */

namespace SpryPhp\Type;

/**
 * TypeRateLimitBy Type
 */
enum TypeRateLimitBy: string
{
    case Ip   = 'ip';
    case user = 'user';
}
