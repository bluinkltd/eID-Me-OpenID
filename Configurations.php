<?php

class eID_Me_OpenID_Config
{
    /**
     * Contains config params for OIDC protocol with eID-Me server
     *
     * @var array
     */
    private $_params;


    public function __construct()
    {
        $basePath = __DIR__ . '/';

        $this->loadConfigParamsFromFile();
    }


    private function loadConfigParamsFromFile()
    {
        $basePath = __DIR__ . '/';

        $filename = $basePath . 'config/' . 'config_params.php';

        if (!file_exists($filename)) {
            throw new \Exception('Config params file not found');
        }

        /** @var array $params */
        include $filename;

        return $this->loadConfigParamsFromArray($params);

    }

    Private function loadConfigParamsFromArray($params)
    {
        $this->_params = $params;
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public function getAttribute($key)
    {
        if (isset($this->_params[$key])){
            return $this->_params[$key];
        }

        return '';
    }

}