<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m150112_220705_oauth_add_token_columns extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $this->addColumnAfter('oauth_tokens', 'accessToken', array(ColumnType::Varchar, 'required' => false), 'pluginHandle');

        $this->addColumnAfter('oauth_tokens', 'secret', array(ColumnType::Varchar, 'required' => false), 'accessToken');

        $this->addColumnAfter('oauth_tokens', 'endOfLife', array(ColumnType::Varchar, 'required' => false), 'secret');

        $this->addColumnAfter('oauth_tokens', 'refreshToken', array(ColumnType::Varchar, 'required' => false), 'endOfLife');

        require_once(CRAFT_PLUGINS_PATH.'oauth/vendor/autoload.php');

        $rows = craft()->db->createCommand()
            ->select('*')
            ->from('oauth_tokens')
            ->queryAll();

        foreach($rows as $row)
        {
            $token = $row['encodedToken'];
            $token = @unserialize(base64_decode($token));

            if($token && is_object($token))
            {
                $attributes = array();
                $attributes['accessToken'] = $token->getAccessToken();

                if(method_exists($token, 'getAccessTokenSecret'))
                {
                    $attributes['secret'] = $token->getAccessTokenSecret();
                }

                $attributes['endOfLife'] = $token->getEndOfLife();
                $attributes['refreshToken'] = $token->getRefreshToken();


                $this->update('oauth_tokens', $attributes, 'id = :id', array(':id' => $row['id']));
            }
        }

        OauthPlugin::log('Dropping the encodedToken column from the structures table...', LogLevel::Info, true);

        $this->dropColumn('oauth_tokens', 'encodedToken');

        OauthPlugin::log('Done dropping the encodedToken column from the structures table.', LogLevel::Info, true);



        return true;
    }
}
