<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Http;

use AcmePhp\Core\Challenge\SolverInterface;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * ACME HTTP solver talking to pebble-challtestsrv.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class MockServerHttpSolver implements SolverInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'http-01' === $authorizationChallenge->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        $request = $this->requestFactory->createRequest('POST', 'http://localhost:8055/add-http01');
        $data = [
            'token' => $authorizationChallenge->getToken(),
            'content' => $authorizationChallenge->getPayload(),
        ];
        $request = $request->withBody($this->streamFactory->createStream(\json_encode($data)));

        $this->client->sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $request = $this->requestFactory->createRequest('POST', 'http://localhost:8055/del-http01');
        $data = [
            'token' => $authorizationChallenge->getToken(),
        ];
        $request = $request->withBody($this->streamFactory->createStream(\json_encode($data)));

        $this->client->sendRequest($request);
    }
}
