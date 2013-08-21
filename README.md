# OAuth  <small>_for Craft Plugin Developers_</small>

The OAuth plugin handles OAuth providers settings & authentication so you can focus on the things that make your plugin different.

- [Installation](#install)
- [Using OAuth in your Craft plugins](#develop)
    - Getting authenticated with a provider
    - Perform authenticated requests to an API
    - Auto-install & update the OAuth plugin
- [Providers](#providers)
- [Authentication](#tokens)
    - [System authentication](#system-authentication)
    - [User authentication](#user-token)
- [Template API](#template-api)
- [OAuth Service API](#template-api)
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

<a id="tokens"></a>
## Tokens

<a id="system-token"></a>
### System token

Set up a system wide token.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('Google', scope, 'analytics.system')}}


<a id="user-token"></a>
### User token

Set up a user specific token.

    {% set scope = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/analytics'
        ] %}

    {{craft.oauth.connect('Google', scope)}}


<a id="template-api"></a>
## Template API

### craft.oauth.connect(providerClass, scope = null, namespace = null)
### craft.oauth.disconnect(providerClass, namespace = null)
### craft.oauth.connectCallback(providerClass)
### craft.oauth.providerInstantiate(providerClass, token = null, scope = null, callbackUrl = null)
### craft.oauth.providerIsConfigured(provider)
### craft.oauth.providerIsConnected(providerClass, scope = null, namespace = null)


<a id="oauth-api"></a>
## OAuth Service API


<a id="license"></a>
## Licensing

OAuth plugin for Craft CMS is free to use for end users.

If you are a developer and want to make use of the OAuth plugin in your plugins, please contact us at hello@dukt.net.

<a id="feedback"></a>
## Feedback

**Please provide feedback!** We want this plugin to make fit your needs as much as possible.
Please [get in touch](mailto:hello@dukt.net), and point out what you do and don't like. **No issue is too small.**

This plugin is actively maintained by [Benjamin David](https://github.com/benjamindavid), from [Dukt](http://dukt.net/).
