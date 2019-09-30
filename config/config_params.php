<?php

$params = array (

    //Client ID registered on eID-Me server
    'client_id' => '<client id registered on eID-Me>',

    //Client Secret registered on eID-Me server
    'client_secret' => '<client secret registered on eID-Me>',


    'client_name' => '<name of relying party registered on eID-Me>',

    //URI for sending authorization code as part of auth code flow
    'redirect_uri' => '<Relying Party URL>/<path to callback function>',
    
    //Response Type
    //For Auth code grant value should be 'code'
    'response_type' => 'code',

    //Domain URL of eID-Me server
    'idp_hostname' => '<eID-Me Server Url (include https://)>',

    //Public key of eID-Me server to verify token signature
    'idp_public_key' => '<public key>',
);