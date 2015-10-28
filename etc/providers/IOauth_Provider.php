<?php
namespace Craft;

/**
 * Interface IPlugin
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.plugins
 * @since     2.1
 */
interface IOauth_Provider
{
    // Public Methods
    // =========================================================================

    /**
     * Get Name
     *
     * @return string
     */
    public function getName();

    /**
     * Create Provider
     *
     * @return string
     */
    public function createProvider();

    /**
     * Get Icon URL
     *
     * @return string
     */
    public function getIconUrl();

    /**
     * Get Scope Docs URL
     *
     * @return string
     */
    public function getScopeDocsUrl();
}