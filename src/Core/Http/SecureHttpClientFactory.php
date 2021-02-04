<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Http;

use AcmePhp\Ssl\KeyPair;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * PSR-18/PSR-17 wrapper to send requests signed with the account KeyPair.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SecureHttpClientFactory
{
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var Base64SafeEncoder
     */
    private $base64Encoder;

    /**
     * @var KeyParser
     */
    private $keyParser;

    /**
     * @var DataSigner
     */
    private $dataSigner;

    /**
     * @var ServerErrorHandler
     */
    private $errorHandler;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $httpClient,
        Base64SafeEncoder $base64Encoder,
        KeyParser $keyParser,
        DataSigner $dataSigner,
        ServerErrorHandler $errorHandler
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->httpClient = $httpClient;
        $this->base64Encoder = $base64Encoder;
        $this->keyParser = $keyParser;
        $this->dataSigner = $dataSigner;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Create a SecureHttpClient using a given account KeyPair.
     */
    public function createSecureHttpClient(KeyPair $accountKeyPair): SecureHttpClient
    {
        return new SecureHttpClient(
            $accountKeyPair,
            $this->requestFactory,
            $this->streamFactory,
            $this->httpClient,
            $this->base64Encoder,
            $this->keyParser,
            $this->dataSigner,
            $this->errorHandler
        );
    }
}
