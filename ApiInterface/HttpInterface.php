<?php
namespace amb\libtest\ApiInterface;

use \Exception;

class HttpInterface
{
    private $callTimeout = 60;
    private $maxRetries = 5;
    private $maxRetryWait = 10;

    public function __construct($callTimeout = 60, $maxRetries = 5, $maxRetryWait = 10)
    {
        $this->callTimeout = $callTimeout;
        $this->maxRetries = $maxRetries;
        $this->maxRetryWait = $maxRetryWait;
    }

    public function call(&$requests, $responseIsJson = True)
    {
        if (is_array($requests)) {
            $this->callMulti($requests);
        } else {
            return $this->callSingle($requests);
        }
    }

    private function callSingle(Request $request)
    {
        $this->prepCurlHandle($request);
        $responseString = curl_exec($request->getCurlHandle());
        $request->response = new Response(curl_getinfo($request->getCurlHandle()), $responseString);
        $request->setCurlHandle(null);
    }

    private function callMulti(&$requests, $depth = 0)
    {
        $curl_multi = curl_multi_init();
        foreach ($requests as &$request) {
            if (!$request->hasResponse()) {
                $this->prepCurlHandle($request);
                curl_multi_add_handle($curl_multi, $request->getCurlHandle());
            }
        }

        $active = null;
        do {
            $multi_return = curl_multi_exec($curl_multi, $active);
        } while ($multi_return == CURLM_CALL_MULTI_PERFORM);

        while ($active && $multi_return == CURLM_OK) {
            if (curl_multi_select($curl_multi) != -1) {
                do {
                    $multi_return = curl_multi_exec($curl_multi, $active);
                    usleep(40);
                } while ($multi_return == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $retry = 0;
        foreach ($requests as &$request) {
            if (!$request->hasResponse()) {
                $curlHandle = $request->getCurlHandle();
                $response = new Response(curl_getinfo($curlHandle), curl_multi_getcontent($curlHandle));
                if (!$response->isOk() && ($depth < $this->maxRetries)) {
                    $retry++;
                } else {
                    $request->response = $response;
                }
                curl_multi_remove_handle($curl_multi, $curlHandle);
                $request->setCurlHandle(null);
            }
        }
        curl_multi_close($curl_multi);
        if ($retry > 0) {
            sleep((((($depth * $retry) / 2) > $this->maxRetryWait) ? $this->maxRetryWait : round(($depth * $retry) / 2)));
            $this->callMulti($requests, $depth + 1);
        }
    }

    private function prepCurlHandle(Request $request)
    {
        $headers = array();
        $curl = curl_init();
        switch ($request->getMethod()) {
            case 'PUT':
                $filename = $request->getData();
                $url_file = strtolower(basename($filename));
                $url .= '/' . $url_file;
                $file_size = filesize($filename);
                if ($fp = fopen($filename, 'r')) {
                    curl_setopt_array($curl, array(
                        CURLOPT_PUT => 1,
                        CURLOPT_INFILE => $fp,
                        CURLOPT_INFILESIZE => $file_size,
                        CURLOPT_CUSTOMREQUEST => 'PUT'
                    ));
                } else {
                    throw new Exception('File unreadable at ' . $filename, '1000');
                }
                break;

            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getData());
                break;

            case 'GET':
                // no special curlopts required
                break;

            default:
                throw new Exception('Invalid http method requested in http interface');
        }

        $headers[] = 'Content-type: ' . $request->getContentType();
        if (($auth = $request->getAuth()) !== null) {
            $headers[] = 'Authorization: ' . $auth->getAuthHeader($request->getEndPoint(), $request->getMethod());
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $request->getEndPoint(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => $this->callTimeout,
            CURLOPT_HTTPHEADER => $headers
        ));
        $request->setCurlHandle($curl);
    }

}
