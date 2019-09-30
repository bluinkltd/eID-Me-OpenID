<?php

class eID_Me_TokenRequest
{
    /**
     * Auth Code from eID-Me server
     *
     * @var string
     */
    private $_tokenParams;

    private $_state;

    private $_nonce;

    /**
     * Hostname of the eID-Me server
     *
     * @var string
     */
    private $_host;

    /**
     * OIDC public key of the eID-Me server
     *
     * @var string
     */
    private $_publicKey;


    /**
     * Initializes the Auth Code request.
     *
     * @param array $query - Set the query for the auth code request.
     */
    public function __construct($params, $host, $public_key, $state, $nonce)
    {
        $this->_tokenParams = $params;
        $this->_host = $host;
        $this->_publicKey = $public_key;
        $this->_state = $state;
        $this->_nonce = $nonce;
    }

    public function sendRequest()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $response = $client->request(
            'POST', $this->_host . '/oidc/token',
            [
                'json' => $this->_tokenParams,
            ]
        );

        $response = json_decode($response->getBody(), true);

        // URL-Safe base64_decode helper
        $b64decode = function($b64)
        {
            $b64 = str_replace(array('-', '_'),
                array('+', '/'),
                $b64);

            return base64_decode($b64);
        };

        \Log::debug('Token response', [$response]);

        // Split up the JWT into the parts we need
        $token_parts = explode('.', $response['id_token']);

        if (count($token_parts) !== 3){
            throw new \Exception('Invalid ID token!');
        }

        $header = json_decode($b64decode($token_parts[0]), true);
        $payload = json_decode($b64decode($token_parts[1]), true);
        $signedData = $token_parts[0] . '.' . $token_parts[1];
        $signature = $b64decode($token_parts[2]);

        $verification = $this->verifySignature($this->_publicKey, $signedData, $signature, $header);

        // Tokens have a validity period, check that it's not expired
        if ($payload['exp'] < time()) {
            throw new \Exception('Error! ID Token expired.');
        }

        // Make sure it's for this RP
        if ($payload['aud'] !== $this->_tokenParams['client_id']) {
            throw new \Exception('Error: ID Token does not match Client ID.');
        }

        // Make sure it's for this server
        if ($payload['iss'] !== $this->_host) {
            throw new \Exception('Error: Issuer does not match OP server host.');
        }

        // Make sure no tampering was done
        // check nonce parameter
        // commented out due to difficulties with laravel sessions
        // please leave in for when we get nonce working correctly
        if (!empty($this->_nonce) && $payload['nonce'] !== $this->_nonce) {
            throw new \Exception('Error: Nonce string does not verify.');
        }

        return $payload;
    }

    private function verifySignature($oidcPublicKey, $signedData, $signature, $header)
    {
        // Load public key from string
        $key = openssl_pkey_get_public($oidcPublicKey);

        // Helper for HMAC verification
        $hash_equals = function ($a, $b)
        {
            if (function_exists('hash_equals')) {
                return hash_equals($a, $b);
            }
            $diff = strlen($a) ^ strlen($b);
            for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
                $diff |= ord($a[$i]) ^ ord($b[$i]);
            }

            return $diff === 0;
        };

        // Do signature verification, multiple supported algos
        $verified = false;
        switch ($header['alg']) {
            case'HS256':
                $verified = $hash_equals(hash_hmac('sha256', $signedData, $key, true), $signature); break;
            case'HS384':
                $verified = $hash_equals(hash_hmac('sha384', $signedData, $key, true), $signature); break;
            case'HS512':
                $verified = $hash_equals(hash_hmac('sha512', $signedData, $key, true), $signature); break;
            case 'RS256':
                $verified = openssl_verify(
                        $signedData, $signature, $key,
                        defined('OPENSSL_ALGO_SHA256') ? OPENSSL_ALGO_SHA256 : 'sha256'
                    ) === 1; break;
            case 'RS384':
                $verified = openssl_verify(
                        $signedData, $signature, $key,
                        defined('OPENSSL_ALGO_SHA384') ? OPENSSL_ALGO_SHA384 : 'sha384'
                    ) === 1; break;
            case 'RS512':
                $verified = openssl_verify(
                        $signedData, $signature, $key,
                        defined('OPENSSL_ALGO_SHA512') ? OPENSSL_ALGO_SHA512 : 'sha512'
                    ) === 1; break;
            default:
                throw new \Exception('Unsupported or invalid signing algorithm.');

        }

        if (!$verified){
            throw new \Exception('Verification failed');
        }

        return true;
    }


}