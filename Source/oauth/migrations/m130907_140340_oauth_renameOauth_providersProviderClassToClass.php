<?php
/**
 * @link      https://dukt.net/craft/oauth/
 * @copyright Copyright (c) 2016, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 */

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m130907_140340_oauth_renameOauth_providersProviderClassToClass extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $providersTable = $this->dbConnection->schema->getTable('{{oauth_providers}}');

        if ($providersTable)
        {
            if($this->renameColumn('{{oauth_providers}}', 'providerClass', 'class')) {
                OauthPlugin::log('Renamed `{{oauth_providers}}`.`providerClass` to `{{oauth_providers}}`.`class`.', LogLevel::Info, true);
            } else {
                OauthPlugin::log('Couldn\'t rename `{{oauth_providers}}`.`providerClass` to `{{oauth_providers}}`.`class`.', LogLevel::Warning);
            }
        }
        else
        {
            OauthPlugin::log('Could not find an `{{oauth_providers}}` table. Wut?', LogLevel::Error);
        }

        return true;
    }
}
