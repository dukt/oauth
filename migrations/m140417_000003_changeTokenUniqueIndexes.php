<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140417_000003_changeTokenUniqueIndexes extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */

    public function safeUp()
    {
        // unique index for 'userMapping' and 'provider'

        $tableName = 'oauth_tokens';

        $tokensTable = $this->dbConnection->schema->getTable('{{'.$tableName.'}}');

        if ($tokensTable)
        {
            $this->dropForeignKey($tableName, 'userId');

            $this->dropIndex($tableName, 'userMapping, provider', true);

            $this->dropIndex($tableName, 'userId, provider', true);

            $this->dropIndex($tableName, 'namespace, provider', true);

            $this->addForeignKey($tableName, 'userId', 'users', 'id', 'CASCADE');
            $this->createIndex($tableName, 'provider, userMapping, namespace', true);
        }

        return true;
    }
}
