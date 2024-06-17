<?php

namespace KvaPay\HttpClient;

use KvaPay\Exception\ApiConnectionException;
use KvaPay\Exception\UnexpectedValueException;

class CurlClient implements ClientInterface
{
    const DEFAULT_TIMEOUT = 60;

    const DEFAULT_CONNECT_TIMEOUT = 20;

    /**
     * @var int
     */
    private $timeout = self::DEFAULT_TIMEOUT;

    /**
     * @var int
     */
    private $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

    /**
     * @var array<int, mixed>|callable|null
     */
    protected $defaultOptions;

    /**
     * singleton object
     *
     * @var self|null
     */
    protected static $instance = null;

    /**
     * @return self
     */
    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @param array<int, mixed>|callable|null $defaultOptions
     */
    public function __construct($defaultOptions = null)
    {
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * @return array<int, mixed>|callable|null
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setTimeout(int $seconds)
    {
        $this->timeout = max($seconds, 0);

        return $this;
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setConnectTimeout(int $seconds)
    {
        $this->connectTimeout = max($seconds, 0);

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * @param string $method
     * @param string $absUrl
     * @param array $headers
     * @param array $params
     * @return array
     */
    private function getRequestOptions( $method,  $absUrl,  $headers = [],  $params = [])
    {
        $method = strtolower($method);
        $options = [];

        if (is_callable($this->defaultOptions)) {
            $options = call_user_func_array($this->defaultOptions, func_get_args());

            if (!is_array($options)) {
                throw new UnexpectedValueException('Non-array value returned by defaultOptions CurlClient callback');
            }
        } elseif (is_array($this->defaultOptions)) {
            $options = $this->defaultOptions;
        }

        if ($method === 'get') {
            $options[CURLOPT_HTTPGET] = 1;

            if (!empty($params)) {
                $absUrl = $absUrl . '?' . http_build_query($params);
            }
        } elseif (in_array($method, ['patch', 'post'])) {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = json_encode($params);

            if ($method === 'patch') {
                $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            }
        } elseif ($method === 'delete') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';

            if (!empty($params)) {
                $absUrl = $absUrl . '?' . http_build_query($params);
            }
        } else {
            throw new UnexpectedValueException("Unrecognized method $method");
        }

        $options[CURLOPT_URL] = $absUrl;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = $this->connectTimeout;
        $options[CURLOPT_TIMEOUT] = $this->timeout;
        $options[CURLOPT_HTTPHEADER] = $headers;

        $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        $options[CURLOPT_SSL_VERIFYPEER] = true;

        return [$options, $absUrl];
    }

    /**
     * @param string $method
     * @param string $absUrl
     * @param array $headers
     * @param array<string, string> $params
     * @return array<string|mixed>
     *
     * @throws ApiConnectionException
     */
    public function request($method, $absUrl, $headers = [], $params = [])
    {
        $data = $this->getRequestOptions($method, $absUrl, $headers, $params);
        $options = $data[0];
        $absUrl = $data[1];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $errno = curl_errno($ch);
            $message = curl_error($ch);

            $this->handleCurlError($absUrl, $errno, $message);
        }

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$responseBody, $responseCode];
    }

    /**
     * @param string $url
     * @param int $errno
     * @param string $message
     * @return void
     *
     * @throws ApiConnectionException
     */
    private function handleCurlError(string $url, int $errno, string $message)
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $response = "Could not connect to KvaPay ($url). "
                    . "Please check your internet connection and try again. ";

                break;

            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $response = "Could not verify KvaPay's SSL certificate. "
                    . "Please make sure that your network is not intercepting certificates. "
                    . "(Try going to $url in your browser.) ";

                break;

            default:
                $response = "Unexpected error communicating with KvaPay. ";
        }

        $response .= 'If this problem persists, let us know at https://app.kvapay.com/support.';

        $response .= "\n\n(Network error [errno $errno]: $message)";

        throw new ApiConnectionException($response);
    }
}
