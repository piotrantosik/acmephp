<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Http;

use AcmePhp\Core\Exception\AcmeCoreException;
use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Http\HttpClient;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Http\ServerErrorHandler;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

class SecureHttpClientTest extends TestCase
{
    /**
     * @param bool $willThrow
     *
     * @return SecureHttpClient
     */
    private function createMockedClient(array $responses, $willThrow = false)
    {
        $keyPairGenerator = new KeyPairGenerator();

        $client = new MockHttpClient($responses);
        $psrClient = new Psr18Client($client);

        $errorHandler = $this->getMockBuilder(ServerErrorHandler::class)->getMock();

        if ($willThrow) {
            $errorHandler->expects($this->once())
                ->method('createAcmeExceptionForResponse')
                ->willReturn(new AcmeCoreException());
        }

        return new SecureHttpClient(
            $keyPairGenerator->generateKeyPair(),
            new HttpClient($psrClient, $psrClient, $psrClient),
            new Base64SafeEncoder(),
            new KeyParser(),
            new DataSigner(),
            $errorHandler
        );
    }

    public function testSignKidPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signKidPayload('/foo', 'account', ['foo' => 'bar']);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertSame('{"foo":"bar"}', \base64_decode($payload['payload']));
    }

    public function testSignKidPayloadWithEmptyPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signKidPayload('/foo', 'account', []);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('{}', \base64_decode($payload['payload']));
    }

    public function testSignKidPayloadWithNullPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signKidPayload('/foo', 'account');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('', \base64_decode($payload['payload']));
    }

    public function testSignJwkPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signJwkPayload('/foo', ['foo' => 'bar']);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('protected', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('signature', $payload);
        $this->assertSame('{"foo":"bar"}', \base64_decode($payload['payload']));
    }

    public function testSignJwkPayloadWithEmptyPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signJwkPayload('/foo', []);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('{}', \base64_decode($payload['payload']));
    }

    public function testSignJwkPayloadWithNullPayload()
    {
        $client = $this->createMockedClient([]);
        $payload = $client->signJwkPayload('/foo');

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertSame('', \base64_decode($payload['payload']));
    }

    public function testValidStringRequest()
    {
        $client = $this->createMockedClient([new MockResponse('foo')]);
        $body = $client->request('GET', 'https://localhost/foo', ['foo' => 'bar'], false);
        $this->assertEquals('foo', $body);
    }

    public function testValidJsonRequest()
    {
        $client = $this->createMockedClient([new MockResponse(json_encode(['test' => 'ok']))]);
        $data = $client->request('GET', 'https://localhost/foo', ['foo' => 'bar'], true);
        $this->assertEquals(['test' => 'ok'], $data);
    }

    public function testInvalidJsonRequest()
    {
        $this->expectException('AcmePhp\Core\Exception\Protocol\ExpectedJsonException');
        $client = $this->createMockedClient([new MockResponse('invalid json')]);
        $client->request('GET', 'https://localhost/foo', ['foo' => 'bar'], true);
    }
}
