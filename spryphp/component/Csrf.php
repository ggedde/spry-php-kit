<?php declare(strict_types=1);
/**
 * CSRF Component
 */

namespace SpryPhp\Component;

use SpryPhp\Provider\Session;

/**
 * Class for Csrf Component
 */
class Csrf
{
    /**
     * Construct the Csrf Component
     */
    public function __construct()
    {
        ?>
        <input type="hidden" name="csrf" value="<?= Session::csrf(); ?>">
        <?php
    }
}
