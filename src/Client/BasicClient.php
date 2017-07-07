<?php

namespace Maleficarum\Client\Http;

/**
 * Basic client that does not do any kind of payload encoding nor response decoding.
 */
class BasicClient extends AbstractClient
{

    /**
     * @inheritdoc
     */
    public function encodePayload()
    {
        return $this->getPostParams();
    }

    /**
     * @inheritdoc
     */
    public function decodeResponse($response)
    {
        return $response;
    }
}