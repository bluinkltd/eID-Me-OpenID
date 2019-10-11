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



Dependencies
-------------

* `php >= 7.0`

* `openssl` used for signature verification in the `id token`

* 'GuzzleHttp' used for back-channel requests to retreive the `id token`



Usage
------

### Configuration ###

Config data can be provided through:

* Modifying the config file `config_params.php` in the `config` directory.

* Providing data in array to `eID_Me_Auth` object constructor from the `Auth.php` file 


Example `config_params.php` file

	<?php

	$params = array (

	    //Client ID registered on eID-Me server
	    'client_id' => '2fd5f5567849548b34e3',

	    //Client Secret registered on eID-Me server
	    'client_secret' => '8e5f5c9784789a0b51762e867a0428e5',

	    'client_name' => 'Demo Relying Party',

	    //URI for sending authorization code as part of auth code flow
	    'redirect_uri' => 'https://demorp.bluink.ca/auth/cb',
	    
	    //Response Type
	    //For Auth code grant value should be 'code'
	    'response_type' => 'code',

	    //Domain URL of eID-Me server
	    'idp_hostname' => 'https://demo.eID-Me.ca',

	    //Public key of eID-Me server to verify token signature
	    'idp_public_key' => '-----BEGIN PUBLIC KEY-----
     MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAutrIirwPB2Cz8D2CJ6NW
     yKtqToYWPlyN0vRaqq+7/twcu1mtfzHc1MynhAMGEkLNkk2dF7+gkLE+fROcoFJa
     EQlrV2+sK51h9H4buD52l0mCx9YayDzntuHdgDudftp1H0egCBmHXYsq1qVd7UKq
     942GU0U2TDVicPoMTturM1YGMM9G0KRUzPS9pYopkgrEk7iwvPvqrfYj8mZQ9r0q
     iXXzGSL+q6mS0ogwkLWvao6dLPcIA7NXrhcnuMbXIyBkbi5ETsTMniWuWr0YpO6O
     eUuhlmzKhhNNonWg3jloZso7DzTSQXt/4HFLlCuxXv1Kul0EV+9Cvc3rpDGvYkj6
     xQIDAQAB
     -----END PUBLIC KEY-----',
	);

### Auth Code Request ###

To send an authorization request to the eID-Me OpenID Provider, list the request parameters in an array and pass them to the `eID_Me_Auth` constructor. Then call the auth code function.

            $params = [
                'client_id' => '2fd5f5567849548b34e3',
                'client_secret' => '8e5f5c9784789a0b51762e867a0428e5',
                'redirect_uri' => 'https://demorp.bluink.ca/auth/cb',
                'scope' => 'openid profile',
                'state' => '950208ea5364',
                'nonce' => 'f8d7ad8be31a',
            ];

            $eid_auth = new eID_Me_Auth($params);
            $redirectUrl = $eid_auth->getAuthCode(true);


*Note that parameters set in the `config_params.php` file, do not need to be set in the `params` array.

The required params that must be passed in the array are:
* scope
* state
* nonce

`State` and `nonce` are parameters needed to ensure that the `code` and `id token` you receive came from the original request.



### ID Token Request ###


Upon receiving the request at the callback endpoint containing the authorization code, retrieve the `state` and `nonce` parameters from the original request, and pass them in an array to the `eID_Me_Auth` constructor. If `client_id`, `client_secret` and `redirect_uri` are not set in the `config_params.php` file, they should be passed as well.

Then call the `getIdToken()` function to retreive the ID Token payload containing `eID-Me claims`.

            //gets the authorization code
            $auth_code = $request->code;

            //gets the state parameter passed in callback to verify 
            //that the auth code is from the original request
            $state = $request->state;

            $params = [
                'client_id' => '2fd5f5567849548b34e3',
                'client_secret' => '8e5f5c9784789a0b51762e867a0428e5',
                'redirect_uri' => 'https://demorp.bluink.ca/auth/cb',
                'state' => '950208ea5364',
                'nonce' => 'f8d7ad8be31a',
            ];

            $eid_auth = new eID_Me_Auth($params);
            $payload = $eid_auth->getIdToken($auth_code, $state);


The `getIdToken()` will validate and parse the `id token` from its `JWT` format and return the payload. The payload will contain the `claims` requested by the `eID-Me` OpenID Provider server from the user's `eID-Me` smartphone app. 







