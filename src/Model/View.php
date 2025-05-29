<?php declare(strict_types=1);
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
     * Returns the Meta information for the page.
     *
     * @throws Exception
     *
     * @return PageMeta
     */
    public function meta(): PageMeta
    {
        throw new Exception('SpryPHP: Missing View File or No View Meta Method Set.');
    }

    /**
     * Outputs the HTML for the View Item.
     *
     * @throws Exception
     *
     * @return void
     */
    public function render(): void
    {
        throw new Exception('SpryPHP: Missing View File or No View Render Method Set.');
    }
}
