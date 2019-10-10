# eID-Me OpenID Client PHP Toolkit

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Add [OpenID](https://openid.net/connect/) authentication with Bluink's eID-Me server to your web service (PHP) through the functions in this library.


OpenID Authorization Code flow
--------------------------------

* Authorization Code request

`response_type=code`

For the OAuth authorization code flow, `response_type` must be set as `code` as defined in RFC 6749. In order for it to be an `OpenID` request, `openid` must be included in the `scope` parameter.


* ID Token Request

Once an authorization code is issued from the authorization endpoint, that code is used to retreive the `id token` from the token endpoint through a back-channel.










