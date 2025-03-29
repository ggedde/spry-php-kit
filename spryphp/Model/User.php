<?php declare(strict_types = 1);
/**
 * This file is to handle Users
 */

namespace SpryPhp\Model;

use SpryPhp\Provider\Functions;

/**
 * Class for managing and rendering users
 */
class User
{
    /**
     * User Id
     *
     * @var string $id
     */
    public string $id = '';

    /**
     * User Type
     *
     * @var string $type - admin | user
     */
    public string $type = '';

    /**
     * User Name
     *
     * @var string $name
     */
    public string $name = '';

    /**
     * Construct a View
     *
     * @param string $id   - Id of User
     * @param string $type - Type of User. Either "admin" or "user"
     * @param string $name - Name of User
     */
    public function __construct(string $id, string $type, string $name)
    {
        if (!in_array($type, ['admin', 'user'], true)) {
            Functions::abort('Wrong User Type Passed. Must be one of '.implode(', ', ['admin', 'user']));
        }

        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
    }
}
