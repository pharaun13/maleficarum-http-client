<?php
/**
 * This is the REST specific basic client implementation.
 */
declare(strict_types=1);

namespace Maleficarum\Client\Http\Rest;

class BasicClient extends \Maleficarum\Client\Http\Basic\AbstractClient {
    /* ------------------------------------ Class Methods START ---------------------------------------- */
    
    /**
     * @see \Maleficarum\Client\Http\Basic\AbstractClient::encodePayload()
     */
    protected function encodePayload($data) {
        return \json_encode($data);
    }

    /**
     * @see \Maleficarum\Client\Http\Basic\AbstractClient::decodeResponseBody()
     */
    protected function decodeResponseBody(string $responseBody) {
        return \json_decode($responseBody, true);
    }

    /**
     * @see \Maleficarum\Client\Http\Basic\AbstractClient::request()
     */
    public function request(string $url, string $method, array $options = []): void {
        $headers = $options['headers'] ?? [];
        $options['headers'] = \array_merge($headers, ['Content-Type: application/json']);

        parent::request($url, $method, $options);
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}