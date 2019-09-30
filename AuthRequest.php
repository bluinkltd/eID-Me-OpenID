<?php

class eID_Me_AuthRequest
{
    /**
     * Query for Auth Code from eID-Me
     *
     * @var array
     */
    private $_queryParams;

    /**
     * Hostname of the eID-Me server
     *
     * @var string
     */
    private $_host;

    /**
     * Initializes the Auth Code request.
     *
     * @param array $query - Set the query for the auth code request.
     */
    public function __construct($params, $host)
    {
        $this->_queryParams = $params;
        $this->_host = $host;
    }


    public function sendRequest($retrieveUrl)
    {
        $query = http_build_query($this->_queryParams);

        $url = $this->_host . '/oidc/authorize?'.$query;

        if ($retrieveUrl){
            return $url;
        }

        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');
        header('Location: ' . $url);
        exit();
    }

}