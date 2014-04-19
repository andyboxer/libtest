<?php
namespace amb\libtest\ApiInterface;

use \Exception;

class Response
{
    private $data = array();
    private $responseHeaders = array();
    private $rawResponse = null;
    private $curlInfo = null;

    public function __construct($curlInfo, $responseString)
    {
        $this->curlInfo = $curlInfo;
        $this->rawResponse = $responseString;
        $this->parseHeaders(substr($responseString, 0, $curlInfo['header_size']));
        $this->parseResponse(substr($responseString, $curlInfo['header_size']));
    }

    private function parseHeaders($responseHeaders)
    {
        $headers = preg_split("/[\n\r]+/", $responseHeaders, -1, PREG_SPLIT_NO_EMPTY);
        if (count($headers) > 0) {
            $this->parseStatus(array_shift($headers));
            foreach ($headers as $header) {
                $name = stristr($header, ":", true);
                $value = trim(stristr($header, ":"), ': ');
                if (strlen($value) > 0) {
                    $value = preg_split("/;/", $value, -1, PREG_SPLIT_NO_EMPTY);
                    array_walk($value, 'trim');
                    if (count($value) == 1) {
                        $value = $value[0];
                    }
                } else {
                    $value = '';
                }
                $this->responseHeaders[strtolower($name)] = $value;
            }
        }
    }

    private function parseStatus($statusLine)
    {
        $this->responseHeaders['HTTP_STATUS_LINE'] = $statusLine;
        $parts = array();
        preg_match("/^(HTTPS?\/\d\.\d) (\d{3})(.*)/", $statusLine, $parts);
        $this->responseHeaders['HTTP_PROTOCOL'] = $parts[1];
        if (count($parts) > 3) {
            $this->responseHeaders['HTTP_REMOTE_HELO'] = trim($parts[3]);
        }
        $this->responseHeaders['HTTP_STATUS_CODE'] = $this->curlInfo['http_code'];
    }

    private function parseResponse($responseString)
    {
        $c = $this->getContentType();
        if (preg_match("/^application\/javascript/i", $c) || preg_match("/^application\/json/i", $c)) {
            $this->parseJson($responseString);
        } else {
            $this->rawResponse = $responseString;
        }
    }

    public function getContentType()
    {
        $contentType = $this->responseHeaders['content-type'];
        if (is_array($contentType)) {
            return $contentType[0];
        }
        return $contentType;
    }

    private function parseJson(&$responseString)
    {
        $response = json_decode(preg_replace("/^while\(1\);/", '', $responseString), true);
        if ($response === null) {
            throw new Exception("Json response received with invalid JSON in context body");
        } elseif (is_array($response)) {
            $this->data = array_merge($this->data, $response);
        } else {
            $this->data = array(
                $response
            );
        }
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    public function getResponseHeader($name)
    {
        if (isset($this->responseHeaders[$name])) {
            return $this->responseHeaders[$name];
        }
        return null;
    }

    public function isOk()
    {
        return preg_match('/^2/', $this->responseHeaders['HTTP_STATUS_CODE']) && ($this->BAD_JSON !== true);
    }

    public function getErrorMsg()
    {
        $msg = "Code : $this->HTTP_CODE";
        foreach ($this->data as $key => $value) {
            if ($key !== 'HTTP_CODE') {
                $msg .= ', ' . $key . ": " . $value;
            }
        }
        return $msg;
    }

    public function count()
    {
        return count($this->data);
    }

    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function getData()
    {
        return $this->data;
    }

    public function __toString()
    {
        return json_encode($this->data);
    }
}
