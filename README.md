# OAuth  <small>_for Craft Plugin Developers_</small>

The OAuth plugin handles OAuth providers settings & authentication so you can focus on the things that make your plugin different.

- [Installation](#install)
- [Using OAuth in your Craft plugins](#develop)
    - Getting authenticated with a provider
    - Perform authenticated requests to an API
    - Auto-install & update the OAuth plugin
- [Providers](#providers)
- [Authentication](#authentication)
    - [Authentication for the the system](#system-authentication)
    - [Authentication for users](#user-authentication)
- [Templating Reference](#template-api)
- [OAuthService API](#template-api)
- [OAuthProvider Object API](#provider-api)
- [Understanding the OAuth authentication process](#api)
- [Licensing](#license)
- [Feedback](#feedback)

<a id="installation"></a>
## Installation

Unzip and drop the OAuth plugin in your `craft/plugin` directory.

<a id="develop"></a>
## Using OAuth in your plugins

The OAuth plugin handles OAuth providers settings & authentication so you can focus on the things that make your plugin different.

Popular providers are supported such as Facebook, Google, Twitter and <a href="#providers">more...</a>

There <a href="#tokens">are two ways</a> to get connected :

- **System :** Authentication between your _Craft system_ and a provider.
- **User :** Authentication between a _Craft user_ and provider.

Once connected, it's easy to make <a href="#">authenticated requests to any API</a> you choose to use.

Finally, you can <a href="#">add OAuth plugin auto-install and update</a> in order to make OAuth authentication even more integrated in your plugin.

<a id="providers"></a>
## Providers

- Facebook
- GitHub

- Google
- Twitter
- Flickr

The following providers are **not supported** but will be added soon :

- Appnet
- Dropbox
- Foursquare
- Instagram
- LinkedIn
- Mailchimp
- PayPal
- Tumblr
- Vimeo

<a id="authentication"></a>
## Authentication

<a id="system-authentication"></a>
### Authentication for the the system

Set up a system wide token.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('Google', scope, 'analytics.system')}}


<a id="user-authentication"></a>
### Authentication for users

Set up a user specific token.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('Google', scope)}}


<a id="template-api"></a>
## Templating API

<dl>
    <dt><tt>craft.oauth.connect(providerClass, scope = null, namespace = null)</tt></dt>
    <dt><tt>craft.oauth.disconnect(providerClass, namespace = null)</tt></dt>
    <dt><tt>craft.oauth.callbackUrl(providerClass)</tt></dt>
    <dt><tt>craft.oauth.getAccount(providerClass, namespace = null)</tt></dt>
    <dt><tt>craft.oauth.getProvider(providerClass, configuredOnly = true)</tt></dt>
    <dt><tt>craft.oauth.getProviders(configuredOnly = true)</tt></dt>
    <dt><tt>craft.oauth.getSystemToken(providerClass, namespace)</tt></dt>
    <dt><tt>craft.oauth.getSystemTokens()</tt></dt>
    <dt><tt>craft.oauth.getUserToken(providerClass, userId = null)</tt></dt>
    <dt><tt>craft.oauth.getUserTokens(userId = null)</tt></dt>
</dl>

<a id="oauth-api"></a>
## OauthService API

<dl>
    <dt><tt>craft()->oauth->callbackUrl($providerClass)</tt></dt>
    <dt><tt>craft()->oauth->connect($providerClass, $scope = null, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->disconnect($providerClass, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->getAccount($providerClass, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->getProvider($handle, $configuredOnly = true)</tt></dt>
    <dt><tt>craft()->oauth->getProviders($configuredOnly = true)</tt></dt>
    <dt><tt>craft()->oauth->getSystemToken($providerClass, $namespace)</tt></dt>
    <dt><tt>craft()->oauth->getSystemTokens()</tt></dt>
    <dt><tt>craft()->oauth->getToken($providerClass, $namespace = null, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getTokenEncoded($encodedToken)</tt></dt>
    <dt><tt>craft()->oauth->getTokenRecord($providerClass, $namespace = null, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getUserToken($providerClass, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getUserTokens($userId = null)</tt></dt>
    <dt><tt>craft()->oauth->httpSessionAdd($k, $v = null)</tt></dt>
    <dt><tt>craft()->oauth->httpSessionClean()</tt></dt>
    <dt><tt>craft()->oauth->scopeIsEnough($scope1, $scope2)</tt></dt>
    <dt><tt>craft()->oauth->scopeMix($scope1, $scope2)</tt></dt>
</dl>


<a id="provider-api"></a>
## OAuthProvider Object API

### Properties

<dl>
    <dt><tt>isConfigured</tt></dt>
    <dt><tt>record</tt></dt>
    <dt><tt>providerSource</tt></dt>
</dl>

## Methods

<dl>
    <dt><tt>connect($token = null, $scope = null)</tt></dt>
    <dt><tt>getScope()</tt></dt>
    <dt><tt>getAccount()</tt></dt>
    <dt><tt>token()</tt></dt>
    <dt><tt>getName()</tt></dt>
    <dt><tt>getClassHandle()</tt></dt>
    <dt><tt>hasScope($scope, $namespace = null)</tt></dt>
</dl>

<a id="license"></a>
## Licensing

OAuth plugin for Craft CMS is free to use for end users.

If you are a developer and want to make use of the OAuth plugin in your plugins, please contact us at hello@dukt.net.

<a id="feedback"></a>
## Feedback

**Please provide feedback!** We want this plugin to make fit your needs as much as possible.
Please [get in touch](mailto:hello@dukt.net), and point out what you do and don't like. **No issue is too small.**

This plugin is actively maintained by [Benjamin David](https://github.com/benjamindavid), from [Dukt](http://dukt.net/).
