<?php
/**
 * This is the REST specific multi client implementation.
 */
declare(strict_types=1);

namespace Maleficarum\Client\Http\Rest;

class MultiClient extends \Maleficarum\Client\Http\Multi\AbstractClient {
    /* ------------------------------------ Class Methods START ---------------------------------------- */
    
    /**
     * @see \Maleficarum\Client\Http\Multi\AbstractClient::request()
     */
    public function request(string $url, string $method, array $options = []): void {
        $headers = $options['headers'] ?? [];
        $options['headers'] = \array_merge($headers, ['Content-Type: application/json']);

        parent::request($url, $method, $options);
    }

    /**
     * @see \Maleficarum\Client\Http\Multi\AbstractClient::encodePayload()
     */
    protected function encodePayload($data) {
        return \json_encode($data);
    }

    /**
     * @see \Maleficarum\Client\Http\Multi\AbstractClient::decodeResponseBody()
     */
    protected function decodeResponseBody(string $responseBody) {
        return \json_decode($responseBody, true);
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}