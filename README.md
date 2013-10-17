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
- [Oauth_ProviderModel](#Oauth_ProviderModel)
- [Oauth_TokenModel](#Oauth_TokenModel)
- [Troubleshooting](#Troubleshooting)
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

APIs will usually ask a **token** in order to let you perform authenticated requests. Here is how to get it:

    $token = craft()->oauth->getToken('facebook');

    $token->getRealToken();

Sometimes, they might also ask for **provider** infos :

    $provider = craft()->oauth->getProvider('facebook');

    $provider->clientId;
    $provider->clientSecret;
    $provider->getRedirectUri();

You can then reuse these informations in order to make authenticated calls.

<a id="develop-auto"></a>
### Auto-install & update the OAuth plugin

_This chapter is not ready yet._

_You can take a look at Facebook or Analytics plugins to see how we've integrated OAuth auto-install & update feature but we're still working on making this easier to setup for you and it's not ready yet._

_(And secretely crossing our fingers to see Craft Plugin Store with dependencies ready before we get time to work on this. ;))_


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
    <dt><tt>craft()->oauth->getProvider($handle, $configuredOnly = true)</tt></dt>
    <dt><tt>craft()->oauth->getProviders($configuredOnly = true)</tt></dt>
    <dt><tt>craft()->oauth->getToken($handle, $namespace = null, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getTokenEncoded($encodedToken)</tt></dt>
    <dt><tt>craft()->oauth->getSystemToken($handle, $namespace)</tt></dt>
    <dt><tt>craft()->oauth->getSystemTokens()</tt></dt>
    <dt><tt>craft()->oauth->getUserToken($handle, $userId = null)</tt></dt>
    <dt><tt>craft()->oauth->getUserTokens($userId = null)</tt></dt>
    <dt><tt>craft()->oauth->providerSave(Oauth_ProviderModel $model)</tt></dt>
    <dt><tt>craft()->oauth->tokenDeleteById($id)</tt></dt>
    <dt><tt>craft()->oauth->tokenDeleteByNamespace($handle, $namespace)</tt></dt>
    <dt><tt>craft()->oauth->tokenSave(Oauth_TokenModel $model)</tt></dt>
    <dt><tt>craft()->oauth->sessionAdd($k, $v = null)</tt></dt>
    <dt><tt>craft()->oauth->sessionClean()</tt></dt>
    <dt><tt>craft()->oauth->scopeIsEnough($scope1, $scope2)</tt></dt>
    <dt><tt>craft()->oauth->scopeMix($scope1, $scope2)</tt></dt>
    <dt><tt>craft()->oauth->getProviderSource($providerClass)</tt></dt>
</dl>


<a id="#Oauth_ProviderModel"></a>
## Oauth_ProviderModel

**Properties**

<dl>
    <dt><tt>id</tt></dt>
    <dt><tt>class</tt></dt>
    <dt><tt>clientId</tt></dt>
    <dt><tt>clientSecret</tt></dt>
</dl>

**Methods**

<dl>
    <dt><tt>getAccount()</tt></dt>
    <dt><tt>getConsoleUrl()</tt></dt>
    <dt><tt>getHandle()</tt></dt>
    <dt><tt>getName()</tt></dt>
    <dt><tt>getRedirectUri()</tt></dt>
    <dt><tt>getSource()</tt></dt>
    <dt><tt>getToken()</tt></dt>
    <dt><tt>getScope()</tt></dt>
    <dt><tt>isConfigured()</tt></dt>
    <dt><tt>setToken($token)</tt></dt>
    <dt><tt>setScope($scope)</tt></dt>
</dl>

<a id="#Oauth_TokenModel"></a>
## Oauth_TokenModel

**Properties**

<dl>
    <dt><tt>id</tt></dt>
    <dt><tt>userMapping</tt></dt>
    <dt><tt>namespace</tt></dt>
    <dt><tt>provider</tt></dt>
    <dt><tt>scope</tt></dt>
    <dt><tt>token</tt></dt>
    <dt><tt>userId</tt></dt>
</dl>



**Methods**

<dl>
    <dt><tt>getDecodedToken()</tt></dt>
    <dt><tt>getEncodedToken()</tt></dt>
    <dt><tt>hasScope($scope)</tt></dt>
</dl>


<a id="troubleshooting"></a>
## Troubleshooting


**Google Too Many Active Tokens**

If you are getting an error like this :

    file_get_contents(https://accounts.google.com/o/oauth2/token):
    failed to open stream: HTTP request failed! HTTP/1.0 400
    google_too_many_active_tokens

You are probalby in the situation where you have too manu tokens associated to your Google Account.

Go to [Google Tokens Management dashboard](https://accounts.google.com/IssuedAuthSubTokens) and revoke some tokens that you don't use anymore.


<a id="license"></a>
## Licensing

OAuth plugin for Craft CMS is planned to be free to use for end-users, with a commercial developer license for people willing to use it in their plugins.

Licensing details are still being worked and licensing might be subject to change between beta and final release. If you have any questions, reach us at : [hello@dukt.net](mailto:hello@dukt.net)

<a id="feedback"></a>
## Feedback

**Please provide feedback!** We want this plugin to make fit your needs as much as possible.
Please [get in touch](mailto:hello@dukt.net), and point out what you do and don't like. **No issue is too small.**

This plugin is actively maintained by [Benjamin David](https://github.com/benjamindavid), from [Dukt](http://dukt.net/).
