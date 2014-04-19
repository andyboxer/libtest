<?php
namespace amb\libtest\ApiInterface;
use \Exception;

class ApiLogin extends AnobiiApiInterface
{
    private $callRoute = 'person/login';
    private $credentials = array();

    public function login($email, $passwd)
    {
        $this->credentials['email'] = $email;
        $this->credentials['password'] = $passwd;
        $this->callLogin();
    }

    /**
     * Log into the API
     *
     * @param type $login
     * @return type
     */
    public function callLogin()
    {
        $request = new Request("{$this->getBaseUri()}{$this->callRoute}", "POST", json_encode($this->credentials), $this->auth);
        $this->httpInterface->call($request);
        if ($request->response->isOK()) {
            $this->credentials['personId'] = $request->response->person_id;
            $this->credentials['sessionId'] = $request->response->session_id;
            $this->credentials['token'] = $request->response->token;
            $this->auth->setLogin($this);
        } else {
            throw new exception("Login failed for user '{$this->credentials['email']}' : {$request->response->getErrorMsg()}");
        }
    }

    public function __get($name)
    {
        if (isset($this->credentials[$name])) {
            return $this->credentials[$name];
        }
        return null;
    }
}
