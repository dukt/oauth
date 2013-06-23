# OAuth  <small>_for Craft CMS_</small>

OAuth has ben designed to help plugin developers get quickly started with OAuth. Discover how to auto-install it, and use it from your your plugin.

- [Installation](#install)
- [Supported providers](#providers)
- [OAuth Authentication](#authentication)
- [Using OAuth in your plugins](#developers)
- [API Reference](#api)
- [Licensing](#license)
- [Feedback](#feedback)

<a id="installation"></a>
## Installation

Unzip and drop the OAuth plugin in your `craft/plugin` directory.

<a id="providers"></a>
## Supported providers

- Google
- Facebook
- Twiiter
- Flickr

<a id="authentication"></a>
## OAuth Authentication

All you have to do is define a namespace, a provider, a scope and choose if the authentication is related to the current user.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('analytics.sytem', 'Google', scope, false)}}

Of course, you can create basic wrappers in your Plugin, in order to simplify templates. For example, our Connect plugin for Craft has a simplified alias to the authenticate method, where doing this :

    {{craft.connect.login()}}

Does the same as this :

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email'
        ] %}

    {{craft.oauth.connect('connect.user', 'Google', scope, true)}}

<a id="developers"></a>
## Using OAuth in your plugin

If you're a developer and want to make use of the OAuth authentication in your plugins, you can ajust your code to make it auto download / install the OAuth plugin if required.

In order to get a provider up and running, you need to go through three simple steps :

- Auto download / install / enable the OAuth plugin
- Configure provider's Client ID & Secret
- Authenticate

### Example Plugin Templates

#### oauth/index.html
#### oauth/_install.html
#### oauth/_configure.html
#### oauth/_authenticate.html

<a id="api"></a>
## API Reference

### craft.oauth.connect(namespace, providerClass, scope = null, userToken = false)

Returns a link for connecting to the provider with given parameters

### craft.oauth.disconnect(namespace, providerClass)

Returns a link for disconnecting from the given provider

### craft.oauth.providerIsConfigured(provider)

Returns true or false

### craft.oauth.providerIsConnected(namespace, providerClass, user = NULL)

Returns true or false


### craft.oauth.getProviders()

Returns an array of all providers found

### craft.oauth.getProvider(providerClass)

Returns an Oauth_ProviderRecord from given provider class

### craft.oauth.getTokens(namespace = null, providerClass = null, userToken = null)

Returns an array of Oauth_TokenRecord

### craft.oauth.getAccount(namespace, providerClass)

Return an account object

<a id="license"></a>
## Licensing

OAuth plugin for Craft CMS is free to use for end users.

If you are a developer and want to make use of the OAuth plugin in your plugins, please contact us at hello@dukt.net.

<a id="feedback"></a>
## Feedback

**Please provide feedback!** We want this plugin to make fit your needs as much as possible.
Please [get in touch](mailto:hello@dukt.net), and point out what you do and don't like. **No issue is too small.**

This plugin is actively maintained by [Benjamin David](https://github.com/benjamindavid), from [Dukt](http://dukt.net/).
