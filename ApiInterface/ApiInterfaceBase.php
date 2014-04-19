<?php
namespace amb\libtest\ApiInterface;

class ApiInterfaceBase
{
    protected $protocol;
    protected $hostName;
    protected $hostPort;
    protected $apiVersion;
    protected $httpInterface;
    protected $auth;

    public function __construct($protocol, $hostName, $hostPort, $apiVersion, $auth = null)
    {
        $this->protocol = $protocol;
        $this->hostName = $hostName;
        $this->hostPort = $hostPort;
        $this->apiVersion = $apiVersion;
        $this->auth = $auth;
        $this->httpInterface = new HttpInterface();
    }

    protected function getBaseUri()
    {
        return $this->protocol . '://' . $this->hostName . ':' . $this->hostPort . '/' . $this->apiVersion . '/';
    }
}