<?php declare(strict_types=1);
/**
 * This file is to handle The PageMeta Class
 */

namespace SpryPhp\Model;

/**
 * PageMeta Instance
 */
class PageMeta
{
	/**
     * Construct the PageMeta
     *
     * @param string $title       Page Title
     * @param string $description Page Description
     * @param bool   $index       Whether to add the follow meta tag
     * @param bool   $follow      Whether to add the follow meta tag
     * @param string $headHtml    Additional Head HTML Code
     */
    public function __construct(
        public string $title       = '',
        public string $description = '',
        public bool   $index       = true,
        public bool   $follow      = true,
        public string $headHtml    = '',
    ) {
        // Do Something
    }
}
