<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140623_130304_oauth_new_tokens_table extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        // move current token table to old

        if (craft()->db->tableExists('oauth_tokens'))
        {
            $this->renameTable('oauth_tokens', 'oauth_old_tokens');
        }

        // create new token table

        if (!craft()->db->tableExists('oauth_tokens', true))
        {
            $this->createTable('oauth_tokens', array(
                'providerHandle' => array('required' => true),
                'pluginHandle'   => array('required' => true),
                'encodedToken'   => array('column' => 'text'),
            ), null, true);
        }

        return true;
    }
}
