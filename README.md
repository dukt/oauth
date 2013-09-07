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

The provider you need is not listed here ? [Ask for its addition](mailto:hello@dukt.net) !


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

    {{craft.oauth.connect('google', scope, 'analytics.system')}}

**Disconnect**

    {{craft.oauth.disconnect('google', 'analytics.system')}}

<a id="user"></a>
### User authentication

**Connect**

Make the connection between _Craft's current user_ and a provider.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('google', scope)}}

**Disconnect**

    {{craft.oauth.disconnect('google')}}

**Managing connections with providers**

The easiest way to let users manage connections with providers is to set up an OAuth Connect FieldType. It will display a table with all configured providers and their connect/disconnect buttons.

You can also allow provider connection management from your templates :

    <table>
        {% for provider in craft.oauth.getProviders() %}
            <tr>
                <th>{{provider.name}}</th>
                <td>

                    {% set token = craft.oauth.getUserToken(provider.handle) %}

                    {% if token %}
                        <p><a href="{{craft.oauth.disconnect(provider.handle)}}">Disconnect</a></p>
                    {% else %}
                        <p><a href="{{ craft.oauth.connect(provider.handle) }}">Connect</a></p>
                    {% endif %}

                </td>
            </tr>
        {% endfor %}
    </table>


<a id="develop-api"></a>
### Perform authenticated requests to an API

Most APIs will ask you several informations (provider infos, token) in order to perform authenticated requests.

Here is how you can get them :

**Provider**

    $provider = craft()->oauth->getProvider('facebook');

    $provider->getClientId();
    $provider->getClientSecret();
    $provider->getRedirectUri();


**Token**

    $token = craft()->oauth->getToken('facebook');

    $token->getDecodedToken();

You can then reuse these informations in order to make authenticated calls.

<a id="develop-auto"></a>
### Auto-install & update the OAuth plugin

_This chapter is not ready yet._


<a id="templating"></a>
## Templating Reference

<dl>
    <dt><tt>craft.oauth.connect(handle, scope = null, namespace = null)</tt></dt>
    <dt><tt>craft.oauth.disconnect(handle, namespace = null)</tt></dt>
    <dt><tt>craft.oauth.callbackUrl(handle)</tt></dt>
    <dt><tt>craft.oauth.getAccount(handle, namespace = null)</tt></dt>
    <dt><tt>craft.oauth.getProvider(handle, configuredOnly = true)</tt></dt>
    <dt><tt>craft.oauth.getProviders(configuredOnly = true)</tt></dt>
    <dt><tt>craft.oauth.getSystemToken(handle, namespace)</tt></dt>
    <dt><tt>craft.oauth.getSystemTokens()</tt></dt>
    <dt><tt>craft.oauth.getUserToken(handle, userId = null)</tt></dt>
    <dt><tt>craft.oauth.getUserTokens(userId = null)</tt></dt>
</dl>

<a id="service-api"></a>
## OauthService API

<dl>
    <dt><tt>craft()->oauth->callbackUrl($handle)</tt></dt>
    <dt><tt>craft()->oauth->connect($handle, $scope = null, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->disconnect($handle, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->getAccount($handle, $namespace = null)</tt></dt>
    <dt><tt>craft()->oauth->getProvider($handle)</tt></dt>
    <dt><tt>craft()->oauth->getSystemToken($handle, $namespace)</tt></dt>
    <dt><tt>craft()->oauth->getSystemTokens()</tt></dt>
    <dt><tt>craft()->oauth->getToken($handle, $namespace = null, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getTokenEncoded($encodedToken)</tt></dt>
    <dt><tt>craft()->oauth->getUserToken($handle, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getUserTokens($userId = null)</tt></dt>
    <dt><tt>craft()->oauth->sessionAdd($k, $v = null)</tt></dt>
    <dt><tt>craft()->oauth->sessionClean()</tt></dt>
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
- getHandle()
- getName()
- getScope()
- getSource()
- getToken()
- hasScope($scope, $namespace = null)


<a id="license"></a>
## Licensing

OAuth plugin for Craft CMS is free to use for end users.

If you are a developer and want to make use of the OAuth plugin in your plugins, please contact us at hello@dukt.net.

<a id="feedback"></a>
## Feedback

**Please provide feedback!** We want this plugin to make fit your needs as much as possible.
Please [get in touch](mailto:hello@dukt.net), and point out what you do and don't like. **No issue is too small.**

This plugin is actively maintained by [Benjamin David](https://github.com/benjamindavid), from [Dukt](http://dukt.net/).
