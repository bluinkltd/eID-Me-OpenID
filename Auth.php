<?php

class eID_Me_Auth
{
    /**
     * eID-Me OpenID Connect relying party configurations
     *
     * @var array
     */
    private $_config;

    /**
     * Name of the relying party
     *
     * @var string
     */
    private $_clientName;

    /**
     * Client ID attribute reigsterd with
     * eID-Me for the Relying Party
     *
     * @var string
     */
    private $_clientId;

    /**
     * Client Secret attribute reigsterd with
     * eID-Me for the Relying Party
     *
     * @var string
     */
    private $_clientSecret;

    /**
     * Redirect URI to provide to
     * eID-Me service
     *
     * @var string
     */
    private $_redirectUri;

    /**
     * Response Type to provide to
     * eID-Me service
     *
     * @var string
     */
    private $_responseType;

    /**
     * Redirect URI to provide to
     * eID-Me service
     *
     * @var string
     */
    private $_scope;

    /**
     * OpenID state value to provide to
     * eID-Me service
     *
     * @var string
     */
    private $_state;

    /**
     * Nonce to provide to
     * eID-Me OpenID service
     *
     * @var string
     */
    private $_nonce;

    /**
     * Prompt to provide to
     * eID-Me service
     *
     * @var array
     */
    private $_metadata;

    /**
     * Login hint to provide to
     * eID-Me service
     *
     * @var string
     */
    private $_loginHint;

    /**
     * Prompt to provide to
     * eID-Me service
     *
     * @var string
     */
    private $_prompt;

    /**
     * Initializes the RP OpenId instance.
     *
     * @param array $customConfig - Set configurations for Relying Party on eID Me.
     */
    public function __construct($customConfig = null)
    {
        $this->_config = new eID_Me_OpenID_Config();

        $state = $customConfig['state'] ?? null;
        \Log::error('State', [$customConfig['state']]);
        $nonce = $customConfig['nonce'] ?? null;

        $this->_state = $this->validateStateOrNonce($state, 'state');
        $this->_nonce = $this->validateStateOrNonce($nonce, 'nonce');

        if ($customConfig === null || ! is_array($customConfig)) {
            return;
        }

        if (isset($customConfig['client_name'])){
            $this->_clientName = $customConfig['client_name'];
        }

        if (isset($customConfig['client_id'])){
            $this->_clientId = $customConfig['client_id'];
        }

        if (isset($customConfig['client_secret'])){
            $this->_clientSecret = $customConfig['client_secret'];
        }

        if (isset($customConfig['redirect_uri'])){
            $this->_redirectUri = $customConfig['redirect_uri'];
        }

        if (isset($customConfig['response_type'])){
            $this->_responseType = $customConfig['response_type'];
        }

        $configs = $this->_config;

        if (isset($customConfig['scope'])){
            $this->_scope = $this->addValidScope($customConfig['scope']);
        } else {
            $this->_scope = $this->addValidScope($configs->getAttribute('scope'));
        }

        if (isset($customConfig['metadata'])){
            $this->_metadata = $this->validateMetdata($customConfig['metadata']);
        } else {
            $this->_metadata = $this->validateMetdata($configs->getAttribute('metadata'));
        }

        if (isset($customConfig['login_hint'])){
            $this->_loginHint = $customConfig['login_hint'];
        }

        if (isset($customConfig['prompt'])){
            $this->_prompt = $customConfig['prompt'];
        }
    }

    public function getAuthCode($retrieveUrl = false)
    {
        $configs = $this->_config;

        $queryParams = [
            'client_id' => isset($this->_clientId) ? $this->_clientId : $configs->getAttribute('client_id'),
            'client_secret' => isset($this->_clientSecret) ? $this->_clientSecret : $configs->getAttribute('client_secret'),
            'redirect_uri' => isset($this->_redirectUri) ? $this->_redirectUri : $configs->getAttribute('redirect_uri'),
            'response_type' => isset($this->_responseType) ? $this->_responseType : $configs->getAttribute('response_type'),
            'scope' => $this->_scope,
            'state' => $this->_state,
        ];

        if (! empty($this->_metadata)){
            $queryParams['metadata'] = $this->_metadata;
        }

        if (! empty($this->_nonce)){
            $queryParams['nonce'] = $this->_nonce;
        }

        if (! empty($this->_loginHint)){
            $queryParams['login_hint'] = $this->_loginHint;
        }

        if (! empty($this->_prompt)){
            $queryParams['prompt'] = $this->_prompt;
        }

        $hostname = $configs->getAttribute('idp_hostname');

        $authRequest = new eID_Me_AuthRequest($queryParams, $hostname);
        return $authRequest->sendRequest($retrieveUrl);
    }

    /**
     * Get the ID Token
     * @param $code
     */
    public function getIdToken($code, $state)
    {
        $configs = $this->_config;

        $tokenParams = [
            'client_id' => isset($this->_clientId) ? $this->_clientId : $configs->getAttribute('client_id'),
            'client_secret' => isset($this->_clientSecret) ? $this->_clientSecret : $configs->getAttribute('client_secret'),
            'redirect_uri' => isset($this->_redirectUri) ? $this->_redirectUri : $configs->getAttribute('redirect_uri'),
            'grant_type' => 'authorization_code',
            'code' => urldecode($code),
            'nonce' => $this->_nonce,
        ];

        //check state attribute in request
        if ($state != $this->_state){
            throw new \Exception('State verification failed: ' . $state . ' ' . $this->_state);
        }

        $tokenRequest = new eID_Me_TokenRequest(
            $tokenParams,
            $configs->getAttribute('idp_hostname'),
            $configs->getAttribute('idp_public_key'),
            $this->_state,
            $this->_nonce
        );

        $payload = $tokenRequest->sendRequest();

        return $payload;
    }


    public function getState()
    {
        return $this->_state;
    }

    public function getNonce()
    {
        return $this->_nonce;
    }

    private function validateStateOrNonce($value, $type)
    {
        $generatedVal = bin2hex(random_bytes(6));

        if ($value === null){
            return $generatedVal;
        }

        $stringValue = strval($value);

        if ($type !== 'state' && $type !== 'nonce'){
            return $generatedVal;
        }


        if (strlen($stringValue) > 10) {
            return $value;
        }

        return $generatedVal;
    }

    private function addValidScope($scope)
    {
        $scopeParts = explode(',', $scope);

        $validScope = '';

        foreach ($scopeParts as $part)
        {
            $validScope .= $part . ' ';
        }
        $scope = trim($validScope);

        if (strpos($scope, 'openid') === false){
            $scope .= 'openid ' . $scope;
        }

        return $scope;
    }

    private function validateMetdata($metadata)
    {
        if (! is_array($metadata) || count($metadata) === 0){
            return null;
        }

        return base64_encode(json_encode($metadata));
    }


}