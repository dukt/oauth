# OAuth  <small>_for Craft Plugin Developers_</small>

The OAuth plugin handles OAuth providers settings & authentication so you can focus on the things that make your plugin different.

- [Installation](#install)
- [Supported providers](#providers)
- [Using OAuth in your Craft plugins](#develop)
    - [System authentication](#system)
    - [User authentication](#user)
    - [Perform authenticated requests to an API](#develop-api)
    - [Auto-install & update the OAuth plugin](#develop-auto)
- [Templating Reference](#templating)
- [OauthService API](#service-api)
- [OAuthProvider Object API](#provider-api)
- [Licensing](#license)
- [Feedback](#feedback)

<a id="installation"></a>
## Installation

Unzip and drop the OAuth plugin in your `craft/plugin` directory.

<a id="providers"></a>
## Supported providers

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


<a id="develop"></a>
## Using OAuth in your plugins

<a id="system"></a>
### System authentication

**Connect**

Make the connection between your _Craft system_ and a provider.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('Google', scope, 'analytics.system')}}

**Disconnect**

    {{craft.oauth.disconnect('Google', 'analytics.system')}}

<a id="user"></a>
### User authentication

**Connect**

Make the connection between _Craft's current user_ and a provider.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('Google', scope)}}

**Disconnect**

    {{craft.oauth.disconnect('Google')}}

**Managing connections with providers**

The easiest way to let users manage connections with providers is to set up an OAuth Connect FieldType. It will display a table with all configured providers and their connect/disconnect buttons.

You can also allow provider connection management from your templates :

    <table>
        {% for provider in craft.oauth.getProviders() %}
            <tr>
                <th>{{provider.classHandle}}</th>
                <td>

                    {% set token = craft.oauth.getUserToken(provider.classHandle) %}

                    {% if token %}
                        <p><a href="{{craft.oauth.disconnect(provider.classHandle)}}">Disconnect</a></p>
                    {% else %}
                        <p><a href="{{ craft.oauth.connect(provider.classHandle) }}">Connect</a></p>
                    {% endif %}

                </td>
            </tr>
        {% endfor %}
    </table>


<a id="develop-api"></a>
### Perform authenticated requests to an API

_This chapter is not ready yet._


<a id="develop-auto"></a>
### Auto-install & update the OAuth plugin

_This chapter is not ready yet._


<a id="templating"></a>
## Templating Reference

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

<a id="service-api"></a>
## OauthService API

<dl>
    <dt><tt>craft()->oauth->callbackUrl($providerClass)</tt></dt>
    <dt><tt>craft()->oauth->connect($providerClass, $scope = null, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->disconnect($providerClass, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->getAccount($providerClass, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->getProvider($providerClass)</tt></dt>
    <dt><tt>craft()->oauth->getProviderSource($handle, $configuredOnly = true)</tt></dt>
    <dt><tt>craft()->oauth->getProviderSources($configuredOnly = true)</tt></dt>
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
## OAuthProviderSource Object API

**Properties**

- isConfigured
- isConnected
- _source (chrisnharvey/OAuth instance)

**Methods**

- init()
- connect($token = null, $scope = null)
- getAccount()
- getClassHandle()
- getName()
- getScope()
- getSource()
- getToken() _(formerly token())_
- hasScope($scope, $namespace = null)



<a id="provider-api"></a>
## OAuthProvider Object API

### Properties

<dl>
    <dt><tt>isConfigured</tt></dt>
    <dt><tt>record</tt></dt>
    <dt><tt>providerSource</tt></dt>
</dl>

### Methods

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
