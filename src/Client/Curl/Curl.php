<?php
declare(strict_types=1);

namespace Maleficarum\Client\Http\Curl;

use Maleficarum\Client\Http\Curl\Exception\LogicException;
use Maleficarum\Client\Http\Curl\Exception\RuntimeException;
use Maleficarum\Client\Http\Exception\InvalidArgumentException;

/**
 * Wrapper for PHP's Curl functions.
 * Engine for the Http Client.
 */
class Curl {
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
     * @throws InvalidArgumentException
     */
    public function initialize(string $url): Curl {
        if (empty($url)) {
            throw new InvalidArgumentException('Invalid URL provided. \Maleficarum\Client\Http\Curl::initialize()');
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
     * @throws LogicException
     */
    public function setOptions(array $options): bool {
        if ($this->isInitialized()) {
            throw new LogicException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::setOptions()');
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
     * @throws LogicException
     */
    public function setOption(int $option, $value): bool {
        if ($this->isInitialized()) {
            throw new LogicException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::setOption()');
        }

        return curl_setopt($this->handle, $option, $value);
    }

    /**
     * Perform request
     *
     * @return mixed
     * @throws LogicException
     * @throws RuntimeException
     */
    public function execute() {
        if ($this->isInitialized()) {
            throw new LogicException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::execute()');
        }

        $response = curl_exec($this->handle);
        $errorCode = curl_errno($this->handle);

        if ($errorCode) {
            throw new RuntimeException(sprintf('Curl error: [%d] %s', $errorCode, curl_error($this->handle)));
        }

        return $response;
    }

    /**
     * Get information
     *
     * @param int|null $option
     *
     * @return mixed
     * @throws LogicException
     */
    public function getInfo(?int $option = null) {
        if ($this->isInitialized()) {
            throw new LogicException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::getInfo()');
        }

        $info = null === $option ? curl_getinfo($this->handle) : curl_getinfo($this->handle, $option);

        return $info;
    }

    /**
     * Close curl session
     *
     * @throws LogicException
     */
    public function close(): void {
        if ($this->isInitialized()) {
            throw new LogicException('Initialize cURL session first. \Maleficarum\Client\Http\Curl::close()');
        }

        curl_close($this->handle);
    }

    /**
     * Check if curl session is initialized
     *
     * @return bool
     */
    private function isInitialized(): bool {
        return (empty($this->handle) || !(is_resource($this->handle) && 'curl' === get_resource_type($this->handle)));
    }
}
