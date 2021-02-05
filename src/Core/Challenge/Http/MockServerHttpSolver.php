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
use AcmePhp\Core\Http\HttpClient;
use AcmePhp\Core\Protocol\AuthorizationChallenge;

/**
 * ACME HTTP solver talking to pebble-challtestsrv.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class MockServerHttpSolver implements SolverInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
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
        $request = $this->httpClient->createRequest('POST', 'http://localhost:8055/add-http01');
        $data = [
            'token' => $authorizationChallenge->getToken(),
            'content' => $authorizationChallenge->getPayload(),
        ];
        $request = $request->withBody($this->httpClient->createStream(\json_encode($data)));

        $this->httpClient->sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        $request = $this->httpClient->createRequest('POST', 'http://localhost:8055/del-http01');
        $data = [
            'token' => $authorizationChallenge->getToken(),
        ];
        $request = $request->withBody($this->httpClient->createStream(\json_encode($data)));

        $this->httpClient->sendRequest($request);
    }
}
