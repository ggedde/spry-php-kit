<?php declare(strict_types=1);
/**
 * CSRF View
 */

namespace SpryPhp\View\Common;

use SpryPhp\Provider\Session;

/**
 * Class for Csrf View
 */
class Csrf
{
    /**
     * Construct the Csrf View
     */
    public function __construct()
    {
        ?>
        <input type="hidden" name="csrf" value="<?= Session::getCsrf(); ?>">
        <?php
    }
}
