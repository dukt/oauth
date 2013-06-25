# OAuth  <small>_for Craft CMS_</small>

OAuth has ben designed to help plugin developers get quickly started with OAuth. Discover how to auto-install it, and use it from your your plugin.

- [Installation](#install)
- [Supported providers](#providers)
- [Set up a system token](#system-token)
- [Set up a user token](#user-token)
- [Developer API Reference](#api)
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

<a id="system-token"></a>
## Set up up a system token

Set up a system wide token.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('analytics.sytem', 'Google', scope)}}


<a id="user-token"></a>
## Set up up a user token

Set up a user-specific token.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('analytics.sytem', 'Google', scope, userId)}}


<a id="api"></a>
## Developer API Reference

### craft.oauth.connect(namespace, providerClass, scope = null, userToken = false)

Returns a link for connecting to the provider with given parameters.

### craft.oauth.disconnect(namespace, providerClass)

Returns a link for disconnecting from the given provider.

### craft.oauth.providerIsConfigured(provider)

Returns true or false.

### craft.oauth.providerIsConnected(namespace, providerClass, user = NULL)

Returns true or false.

### craft.oauth.providerCallbackUrl(providerClass)

Return the callback URL of the provider.

### craft.oauth.getProviders()

Returns an array of all providers found.

### craft.oauth.getProvider(providerClass)

Returns an Oauth_ProviderRecord from given provider class.

### craft.oauth.getProviderLibrary(providerClass)

Return a provider library object.

### craft.oauth.getTokens(namespace = null, providerClass = null, userToken = null)

Returns an array of Oauth_TokenRecord.

### craft.oauth.getToken(encodedToken)

Return an Oauth_TokenRecord from its encoded (serialize + base64encode) token.

### craft.oauth.getAccount(namespace, providerClass)

Return an account object.

<a id="license"></a>
## Licensing

OAuth plugin for Craft CMS is free to use for end users.

If you are a developer and want to make use of the OAuth plugin in your plugins, please contact us at hello@dukt.net.

<a id="feedback"></a>
## Feedback

**Please provide feedback!** We want this plugin to make fit your needs as much as possible.
Please [get in touch](mailto:hello@dukt.net), and point out what you do and don't like. **No issue is too small.**

This plugin is actively maintained by [Benjamin David](https://github.com/benjamindavid), from [Dukt](http://dukt.net/).
