<?php
namespace amb\libtest\ApiInterface;

use \Exception;

class Request
{
    private static $METHODS = array(
        'GET',
        'POST',
        'PUT',
        'DELETE'
    );
    private $endPoint = null;
    private $contentType = 'application/json';
    private $method = 'GET';
    private $data = null;
    private $auth = null;
    private $curlHandle = null;
    public $response = null;

    public function __construct($endPoint, $method = 'GET', $data = null, $auth = null, $contentType = 'application/json')
    {
        $this->endPoint = $endPoint;
        $this->setMethod($method);
        $this->data = $data;
        $this->auth = $auth;
        $this->contentType = $contentType;
    }

    public function getEndPoint()
    {
        return $this->endPoint;
    }

    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $method = strtoupper($method);
        if (array_search($method, Request::$METHODS) !== false) {
            $this->method = $method;
        } else {
            throw new Exception('Invalid method in request ' . $method);
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    public function setCurlHandle($handle)
    {
        if ($this->curlHandle !== null) {
            curl_close($this->curlHandle);
        }
        $this->curlHandle = $handle;
    }

    public function hasResponse()
    {
        return $this->response instanceof Response;
    }
}
