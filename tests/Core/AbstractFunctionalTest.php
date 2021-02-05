<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core;

use AcmePhp\Core\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Psr18Client;

abstract class AbstractFunctionalTest extends TestCase
{
    protected function handleChallenge($token, $payload)
    {
        $psrClient = new Psr18Client();
        $httpClient = new HttpClient($psrClient, $psrClient, $psrClient);
        $requestBody = $httpClient->createStream(\json_encode(['token' => $token, 'content' => $payload]));
        $request = $httpClient->createRequest('POST', 'http://localhost:8055/add-http01')->withBody($requestBody);
        $response = $httpClient->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    protected function cleanChallenge($token)
    {
        $psrClient = new Psr18Client();
        $httpClient = new HttpClient($psrClient, $psrClient, $psrClient);
        $requestBody = $httpClient->createStream(\json_encode(['token' => $token]));
        $request = $httpClient->createRequest('POST', 'http://localhost:8055/del-http01')->withBody($requestBody);
        $response = $httpClient->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
