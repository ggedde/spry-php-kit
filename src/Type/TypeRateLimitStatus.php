<?php declare(strict_types=1);
/**
 * This file is to handle TypeRateLimitStatus
 */

namespace SpryPhp\Type;

/**
 * TypeRateLimitStatus Type
 */
enum TypeRateLimitStatus: string
{
    case Active  = 'active';
    case Blocked = 'blocked';
    case Banned  = 'banned';
}
