<?php
namespace amb\libtest\ApiInterface;

class Authentication
{
    private $key = null;
    private $secret = null;
    private $login = null;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Set the login credentials to be used in authentication
     *
     * @param ApiLogin $login - The users loging credentials
     */
    public function setLogin(ApiLogin $login)
    {
        $this->login = $login;
    }

    /**
     * Generate the authentication header
     */
    public function getAuthHeader($url, $method)
    {
        preg_match("/^https?:\/\/.*?(\/.*)/", $url, $matches);
        $requestUri = $matches[1];
        $timestamp = time();
        $nonce = md5(uniqid(rand(), true));
        $method = strtoupper($method);
        $sessionId = ($this->login !== null) ? $this->login->sessionId : 0;
        // prepare the string used to generate the signature
        $signatureBase = sprintf('%s %s %s %d %s', $method, $requestUri, $sessionId, $timestamp, $nonce);
        // generate the signature
        $signature = hash_hmac('sha1', $signatureBase, $this->secret);
        return sprintf('%s %s %s %d %s %s', 'Anobiiv1', $this->key, $sessionId, $timestamp, $nonce, $signature);
    }
}
