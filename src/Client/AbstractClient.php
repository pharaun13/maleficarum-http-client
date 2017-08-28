<?php
/**
 * This class provides functionality common to all HTTP client classes
 */
declare(strict_types=1);

namespace Maleficarum\Client\Http;

use Maleficarum\Client\Http\Curl\Curl;
use Maleficarum\Client\Http\Curl\Exception\CurlException;
use Maleficarum\Client\Http\Exception\BadRequestException;
use Maleficarum\Client\Http\Exception\ConflictException;
use Maleficarum\Client\Http\Exception\ForbiddenException;
use Maleficarum\Client\Http\Exception\HttpRequestException;
use Maleficarum\Client\Http\Exception\NotFoundException;

abstract class AbstractClient {
    /**
     * Internal storage for available HTTP methods
     *
     * @var array
     */
    static protected $availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Internal storage for default curl options
     *
     * @var array
     */
    static protected $defaultOptions = [
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_HEADER => true,
        \CURLOPT_FOLLOWLOCATION => true,
        \CURLOPT_MAXREDIRS => 5,
        \CURLOPT_NOSIGNAL => true,
        \CURLOPT_TIMEOUT => 120
    ];

    /**
     * Options overriding the $defaultOptions per client instance
     *
     * @var array
     */
    protected $customOptions = [];

    /**
     * Internal storage for the curl object
     *
     * @var Curl
     */
    protected $curl;

    /**
     * Internal storage for API URL
     *
     * @var null|string
     */
    protected $apiUrl = null;

    /**
     * Internal storage for response body
     *
     * @var null|string
     */
    protected $body = null;

    /**
     * Internal storage for request info
     *
     * @var null|array
     */
    protected $info = null;

    /**
     * Internal storage for response headers
     *
     * @var null|array
     */
    protected $responseHeaders = null;

    /**
     * Internal storage for response code
     *
     * @var null|int
     */
    protected $responseCode = null;

    /**
     * Internal storage for GET parameters
     *
     * @var array
     */
    protected $getParams = [];

    /**
     * Internal storage for POST parameters
     *
     * @var array
     */
    protected $postParams = [];

    /* ------------------------------------ Magic methods START ---------------------------------------- */
    /**
     * AbstractClient constructor.
     *
     * @param Curl $curl
     * @param null|string $apiUrl
     */
    public function __construct(Curl $curl, string $apiUrl = null) {
        $this->curl = $curl;
        $this->apiUrl = $apiUrl;
    }
    /* ------------------------------------ Magic methods END ------------------------------------------ */

    /**
     * Preform HTTP request
     *
     * @param string $path
     * @param string $method
     * @param array $headers
     *
     * @return $this
     * @throws \RuntimeException
     * @throws CurlException
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws ConflictException
     * @throws NotFoundException
     * @throws HttpRequestException
     */
    public function request(string $path, $method = 'GET', array $headers = []): AbstractClient {
        if (null === $this->getApiUrl()) {
            throw new \RuntimeException(sprintf('API url has not been set! \%s::doRequest()', get_class($this)));
        }

        if (!in_array($method, self::$availableMethods, true)) {
            throw new \RuntimeException(sprintf('Unsupported method provided! \%s::doRequest()', get_class($this)));
        }

        // prepare request url
        $url = $this->prepareUrl($path);
        // prepare request options
        $options = $this->prepareOptions($method, $headers);

        // initialize curl session and set request options
        $curl = $this->getCurl();
        $curl
            ->initialize($url)
            ->setOptions($options);

        // fetch response and info
        $response = $curl->execute();
        $info = $curl->getInfo();

        // close curl session
        $curl->close();

        if (false === $info) {
            throw new \RuntimeException(sprintf('Unable to complete request. %s::request()', get_class($this)));
        }

        // get header part
        $responseHeaders = $this->parseHeader(mb_substr($response, 0, $info['header_size']));
        // get body part
        $responseBody = mb_substr($response, $info['header_size']);

        $this->setInfo($info);
        $this->setResponseCode($info['http_code']);
        $this->setResponseHeaders($responseHeaders);
        $this->setBody($this->decodeResponse($responseBody));

        if ($this->getResponseCode() < 200 || $this->getResponseCode() >= 300) {
            if (400 === $this->getResponseCode()) {
                throw new BadRequestException('400 Bad request');
            }
            if (403 === $this->getResponseCode()) {
                throw new ForbiddenException('403 Forbidden');
            }
            if (404 === $this->getResponseCode()) {
                throw new NotFoundException('404 Not found');
            }
            if (409 === $this->getResponseCode()) {
                throw new ConflictException('409 Conflict');
            }

            throw new HttpRequestException('Response code: ' . $this->getResponseCode());
        }

        // clear request parameters
        $this
            ->setPostParams([])
            ->setGetParams([]);

        return $this;
    }

    /**
     * Prepare url including GET parameters
     *
     * @param string $path
     *
     * @return string
     */
    protected function prepareUrl(string $path): string {
        $url = $this->getApiUrl() . $path;

        if (count($this->getGetParams())) {
            $url .= '?' . http_build_query($this->getGetParams());
        }

        return $url;
    }

    /**
     * Prepare request options
     *
     * @param string $method
     * @param array $headers
     *
     * @return array
     */
    protected function prepareOptions(string $method, array $headers): array {
        $options = array_replace(static::$defaultOptions, $this->customOptions);

        // POST method
        'POST' === $method and $options[\CURLOPT_POST] = true;

        // PUT, PATCH, DELETE
        in_array($method, ['PUT', 'PATCH', 'DELETE'], true) and $options[\CURLOPT_CUSTOMREQUEST] = $method;

        // Headers
        count($headers) and $options[\CURLOPT_HTTPHEADER] = $headers;

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $options[\CURLOPT_POSTFIELDS] = $this->encodePayload();
        }

        return $options;
    }

    /**
     * Parse header
     *
     * @param string $header
     *
     * @return array
     */
    protected function parseHeader(string $header): array {
        $splitHeader = explode("\r\n", trim($header));

        return $splitHeader;
    }

    /* ------------------------------------ Abstract methods START ------------------------------------- */
    /**
     * Encode request payload
     *
     * @return mixed
     */
    abstract public function encodePayload();

    /**
     * Decode response
     *
     * @param string $response
     *
     * @return mixed
     */
    abstract public function decodeResponse(string $response);
    /* ------------------------------------ Abstract methods END --------------------------------------- */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */
    /**
     * Get apiUrl
     *
     * @return null|string
     */
    public function getApiUrl(): ?string {
        return $this->apiUrl;
    }

    /**
     * Set apiUrl
     *
     * @param string $apiUrl
     *
     * @return $this
     */
    public function setApiUrl(string $apiUrl): AbstractClient {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    /**
     * Get body
     *
     * @return null|string
     */
    public function getBody(): ?string {
        return $this->body;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return $this
     */
    protected function setBody($body): AbstractClient {
        $this->body = $body;

        return $this;
    }

    /**
     * Get info
     *
     * @return array|null
     */
    public function getInfo(): ?array {
        return $this->info;
    }

    /**
     * Set info
     *
     * @param array $info
     *
     * @return $this
     */
    protected function setInfo(array $info): AbstractClient {
        $this->info = $info;

        return $this;
    }

    /**
     * Get responseHeaders
     *
     * @return array|null
     */
    public function getResponseHeaders(): ?array {
        return $this->responseHeaders;
    }

    /**
     * Set responseHeaders
     *
     * @param array $responseHeaders
     *
     * @return $this
     */
    protected function setResponseHeaders(array $responseHeaders): AbstractClient {
        $this->responseHeaders = $responseHeaders;

        return $this;
    }

    /**
     * Get responseCode
     *
     * @return int|null
     */
    public function getResponseCode(): ?int {
        return $this->responseCode;
    }

    /**
     * Set responseCode
     *
     * @param int $responseCode
     *
     * @return $this
     */
    protected function setResponseCode(int $responseCode): AbstractClient {
        $this->responseCode = $responseCode;

        return $this;
    }

    /**
     * Get getParams
     *
     * @return array|null
     */
    public function getGetParams(): ?array {
        return $this->getParams;
    }

    /**
     * Set getParams
     *
     * @param array $getParams
     *
     * @return $this
     */
    public function setGetParams(array $getParams): AbstractClient {
        $this->getParams = $getParams;

        return $this;
    }

    /**
     * Get postParams
     *
     * @return array|null
     */
    public function getPostParams(): ?array {
        return $this->postParams;
    }

    /**
     * Set postParams
     *
     * @param array $postParams
     *
     * @return $this
     */
    public function setPostParams(array $postParams): AbstractClient {
        $this->postParams = $postParams;

        return $this;
    }

    /**
     * Get curl
     *
     * @return Curl
     */
    private function getCurl(): Curl {
        return $this->curl;
    }

    /**
     * The number of seconds to wait while trying to connect.
     *
     * @param int $timeout Use 0 to wait indefinitely.
     *
     * @return $this
     */
    public function setConnectionTimeout(int $timeout = 150): AbstractClient {
        $this->customOptions[\CURLOPT_CONNECTTIMEOUT] = (int)$timeout;

        return $this;
    }

    /**
     * The maximum number of seconds to allow cURL functions to execute.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setOperationTimeout(int $timeout = 3600): AbstractClient {
        $this->customOptions[\CURLOPT_TIMEOUT] = (int)$timeout;

        return $this;
    }
    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}
