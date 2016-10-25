<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m161025_000001_oauth_change_tokens_column_types extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        Craft::log('Altering craft_oauth_tokens accessToken, secret and refreshToken columns to text...', LogLevel::Info, true);

        $this->alterColumn('oauth_tokens', 'accessToken', array(ColumnType::Text));
        $this->alterColumn('oauth_tokens', 'secret', array(ColumnType::Text));
        $this->alterColumn('oauth_tokens', 'refreshToken', array(ColumnType::Text));

        Craft::log('Done altering craft_oauth_tokens accessToken, secret and refreshToken columns to text...', LogLevel::Info, true);

        return true;
    }
}
