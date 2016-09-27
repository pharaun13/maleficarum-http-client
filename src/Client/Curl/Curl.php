<?php

namespace Maleficarum\Client\Http\Curl;

use Maleficarum\Client\Http\Curl\Exception\CurlException;

class Curl
{
    /**
     * Internal storage for curl handle
     *
     * @var resource|null
     */
    private $handle = null;

    /**
     * Initialize curl session
     *
     * @param string $url
     *
     * @return Curl
     * @throws \InvalidArgumentException
     */
    public function initialize($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Invalid URL provided. \Maleficarum\Client\Http\Curl::initialize()');
        }

        $this->handle = curl_init($url);

        return $this;
    }

    /**
     * Set request options
     *
     * @param array $options
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function setOptions(array $options)
    {
        if ($this->isInitialized()) {
            throw new \RuntimeException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::setOptions()');
        }

        return curl_setopt_array($this->handle, $options);
    }

    /**
     * Set request option
     *
     * @param int $option
     * @param mixed $value
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function setOption($option, $value)
    {
        if ($this->isInitialized()) {
            throw new \RuntimeException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::setOption()');
        }

        return curl_setopt($this->handle, $option, $value);
    }

    /**
     * Perform request
     *
     * @return mixed
     * @throws \RuntimeException
     * @throws CurlException
     */
    public function execute()
    {
        if ($this->isInitialized()) {
            throw new \RuntimeException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::execute()');
        }

        $response = curl_exec($this->handle);
        $errorCode = curl_errno($this->handle);

        if ($errorCode) {
            throw new CurlException(sprintf('Curl error: [%d] %s', $errorCode, curl_error($this->handle)));
        }

        return $response;
    }

    /**
     * Get information
     *
     * @param int|null $option
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function getInfo($option = null)
    {
        if ($this->isInitialized()) {
            throw new \RuntimeException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::getInfo()');
        }

        $info = null === $option ? curl_getinfo($this->handle) : curl_getinfo($this->handle, $option);

        return $info;
    }

    /**
     * Close curl session
     *
     * @throws \RuntimeException
     */
    public function close()
    {
        if ($this->isInitialized()) {
            throw new \RuntimeException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::close()');
        }

        curl_close($this->handle);
    }

    /**
     * Check if curl session is initialized
     *
     * @return bool
     */
    private function isInitialized()
    {
        return (empty($this->handle) || !(is_resource($this->handle) && 'curl' === get_resource_type($this->handle)));
    }
}
