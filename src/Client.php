<?php

namespace ResellerInterface\Api;

use ResellerInterface\Api\Exception\InvalidRequestException;
use ResellerInterface\Api\Exception\InvalidResponseException;
use ResellerInterface\Api\Response\Response;
use ResellerInterface\Api\Response\ResponseDownload;
use RuntimeException;

class Client
{
    private $client = null;
    private $baseUrl = null;
    private $version = null;
    private $userAgent = null;
    private $session = null;

    const RESPONSE_RESPONSE = "RESPONSE_RESPONSE";
    const RESPONSE_DOWNLOAD = "RESPONSE_DOWNLOAD";
    const RESPONSE_STREAM = "RESPONSE_STREAM";

    const IP_RESOLVE_V4 = "ipv4";
    const IP_RESOLVE_V6 = "ipv6";
    const IP_RESOLVE_ANY = "any";

    /**
     * Client constructor.
     *
     * @param string $baseUrl The API base URL. Defaults to "https://core.resellerinterface.de/".
     * Use "https://core-staging.resellerinterface.de/" for the staging sytem API.
     * @param string $version The API version this client should be using. Can be 'stable' or 'latest'.
     * Defaults to 'stable'.
     * @param array $options Array containing the optional options.
     *    $options = [
     *      'ipResolve' => (string) one of IP_RESOLVE_V4|IP_RESOLVE_V6|IP_RESOLVE_ANY
     *      'userAgent' => (string) something like "yourUseragent/1.0.0"
     *    ]
     */
    public function __construct(
        $baseUrl = "https://core.resellerinterface.de/",
        $version = 'stable',
        $options = []
    ) {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        if ($version === 'stable' || $version === 'latest') {
            $this->version = $version;
        } elseif ($version && $version == (int)$version) {
            $this->version = 'v' . ((int)$version);
        } else {
            throw new RuntimeException('Invalid version provided.');
        }

        $package = file_get_contents(__DIR__ . '/../composer.json');
        $package = json_decode($package, true);

        $this->client = curl_init();
        curl_setopt($this->client, CURLOPT_HEADER, false);
        curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->client, CURLOPT_HEADERFUNCTION, [$this, 'headerFunction']);
        curl_setopt($this->client, CURLOPT_ENCODING, "");

        $this->setUserAgent("api-client-php/" . $package['version'] . " php/" . phpversion());
        $this->setOptions($options);
    }

    /**
     * @return string The API base URL this client is using.
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string The API version this client is using. Can be 'stable' or 'latest'.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options)
    {
        if (isset($options['ipResolve'])) {
            $this->setIpResolve($options['ipResolve']);
        }
        if (isset($options['userAgent'])) {
            $this->setUserAgent($options['userAgent']);
        }
    }

    /**
     * @param string $option
     * @return void
     */
    public function setIpResolve($option)
    {
        if ($option === self::IP_RESOLVE_V4) {
            curl_setopt($this->client, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        } else {
            if ($option === self::IP_RESOLVE_V6) {
                curl_setopt($this->client, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
            } else {
                curl_setopt($this->client, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
            }
        }
    }

    /**
     * @param string $option
     *
     * @return void
     */
    public function setUserAgent($option) {
        $this->userAgent = $option;
        curl_setopt($this->client, CURLOPT_USERAGENT, $option);
    }

    /**
     * @return string
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    public function __destruct()
    {
        @curl_close($this->client);
    }

    /**
     * @param string $username
     * @param string $password
     * @param int|null $resellerId
     *
     * @return Response
     * @throws InvalidResponseException|InvalidRequestException
     */
    public function login($username, $password, $resellerId = null)
    {
        $query = [
            'username' => $username,
            'password' => $password,
        ];
        if ($resellerId) {
            $query['resellerId'] = $resellerId;
        }

        return $this->request('/reseller/login', $query);
    }

    /**
     * @param string $action
     * @param array $params
     * @param string $responseType can be one of: Client::RESPONSE_RESPONSE, Client::RESPONSE_DOWNLOAD, Client::RESPONSE_STREAM
     *
     * @return bool|Response|string
     * @throws InvalidResponseException
     * @throws InvalidRequestException
     */
    public function request($action, $params, $responseType = self::RESPONSE_RESPONSE)
    {
        $action = trim($action, '/');
        $path = explode("/", $action);

        if (!isset($path[0], $path[1])) {
            throw new InvalidRequestException("invalid request action");
        }

        curl_setopt($this->client, CURLOPT_URL, $this->baseUrl . $this->version . '/' . $action);
        curl_setopt($this->client, CURLOPT_POST, true);
        $queryString = $this->buildPostArray($params, [], '');
        curl_setopt($this->client, CURLOPT_POSTFIELDS, $queryString);

        switch ($responseType) {
            case self::RESPONSE_RESPONSE:
            {
                $response = curl_exec($this->client);
                if ($response === false) {
                    throw new InvalidResponseException('Curl-Error: ' . curl_error($this->client));
                }

                return new Response($response);
            }
            case self::RESPONSE_DOWNLOAD:
            {
                $filename = null;
                $filesize = null;
                $filetype = null;

                curl_setopt(
                    $this->client,
                    CURLOPT_HEADERFUNCTION,
                    static function ($client, $headerLine) use (&$filename, &$filesize, &$filetype) {
                        $len = strlen($headerLine);
                        $header = explode(':', $headerLine, 2);
                        if (count($header) < 2) {
                            return $len;
                        }

                        $name = strtolower(trim($header[0]));
                        if ($name === 'content-length') {
                            $filesize = (int)trim($header[1]);
                        }
                        if (($name === 'content-disposition') && preg_match(
                                "/.*filename=[\'\"]?([^\"]+)/",
                                $header[1],
                                $matches
                            )) {
                            $filename = $matches[1];
                        }
                        if($name === 'content-type') {
                            $filetype = trim($header[1]);
                        }

                        return $len;
                    }
                );

                $response = curl_exec($this->client);
                curl_setopt($this->client, CURLOPT_HEADERFUNCTION, [$this, 'headerFunction']);
                if ($response === false) {
                    throw new InvalidResponseException('Curl-Error: ' . curl_error($this->client));
                }

                return new ResponseDownload($response, $filename, $filesize, $filetype);
            }
            case self::RESPONSE_STREAM:
            {
                curl_setopt($this->client, CURLOPT_RETURNTRANSFER, false);

                curl_setopt(
                    $this->client,
                    CURLOPT_HEADERFUNCTION,
                    static function ($client, $headerLine) {
                        $len = strlen($headerLine);
                        $header = explode(':', $headerLine, 2);
                        if (count($header) < 2) {
                            return $len;
                        }

                        $name = strtolower(trim($header[0]));
                        if ($name === 'content-length') {
                            header('Content-Length: ' . trim($header[1]));
                        }
                        if ($name === 'content-type') {
                            header('Content-Type: ' . trim($header[1]));
                        }
                        if ($name === 'content-disposition') {
                            header('Content-Description: File Transfer');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate');
                            header('Pragma: public');
                            header('Content-Disposition: ' . trim($header[1]));
                        }

                        return $len;
                    }
                );

                $response = curl_exec($this->client);
                curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($this->client, CURLOPT_HEADERFUNCTION, [$this, 'headerFunction']);
                if ($response === false) {
                    throw new InvalidResponseException('Curl-Error: ' . curl_error($this->client));
                }

                exit;
            }
            default:
            {
                throw new InvalidRequestException("Unknown responseType");
            }
        }
    }

    /**
     * @param $current
     * @param $return
     * @param $prefix
     *
     * @return array
     */
    private function buildPostArray($current, $return, $prefix)
    {
        foreach ($current as $k => $v) {
            $newPrefix = $prefix ? $prefix . '[' . $k . ']' : $k;
            if (is_array($v)) {
                $return = $this->buildPostArray($v, $return, $newPrefix);
            } else {
                if ($v === true) {
                    $v = "true";
                }
                if ($v === false) {
                    $v = "false";
                }
                $return[$newPrefix] = $v;
            }
        }

        return $return;
    }

    /**
     * @param $client
     * @param $headerLine
     *
     * @return int
     */
    private function headerFunction($client, $headerLine)
    {
        if (preg_match("/^Set-Cookie:\scoreSID=*([^;]*)/mi", $headerLine, $cookie)) {
            $this->session = $cookie[1];
            curl_setopt($client, CURLOPT_COOKIE, "coreSID=" . $cookie[1]);
        }

        return strlen($headerLine);
    }

    /**
     * @param string|null $session
     * @return void
     */
    public function setSession($session) {
        $this->session = $session;
        if(!$session) {
            curl_setopt($this->client, CURLOPT_COOKIE, "");
        } else {
            curl_setopt($this->client, CURLOPT_COOKIE, "coreSID=" . $session);
        }
    }

    /**
     * @return null|string
     */
    public function getSession() {
        return $this->session;
    }
}