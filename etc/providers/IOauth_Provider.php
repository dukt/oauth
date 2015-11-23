<?php
namespace Craft;

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