<?php declare(strict_types=1);
/**
 * This file is to handle the Storage Type
 */

namespace SpryPhp\Type;

/**
 * Storage Type
 */
enum TypeStorage: string
{
    case Db   = 'db';
    case File = 'file';
}
