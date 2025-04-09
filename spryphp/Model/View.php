<?php declare(strict_types = 1);
/**
 * This file is to handle a View Template
 */

namespace SpryPhp\Model;

use Exception;

/**
 * Class for managing and rendering a view
 */
class View
{
    /**
     * Outputs the HTML for the View Item.
     *
     * @throws Exception
     *
     * @return void
     */
    public function render()
    {
        throw new Exception('SpryPHP: Missing View File or No View Render Method Set.');
    }
}
