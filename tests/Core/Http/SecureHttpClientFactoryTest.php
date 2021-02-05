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

use AcmePhp\Core\Http\Base64SafeEncoder;
use AcmePhp\Core\Http\HttpClient;
use AcmePhp\Core\Http\SecureHttpClient;
use AcmePhp\Core\Http\SecureHttpClientFactory;
use AcmePhp\Core\Http\ServerErrorHandler;
use AcmePhp\Ssl\Generator\KeyPairGenerator;
use AcmePhp\Ssl\Parser\KeyParser;
use AcmePhp\Ssl\Signer\DataSigner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Psr18Client;

class SecureHttpClientFactoryTest extends TestCase
{
    public function testCreateClient()
    {
        $keyPair = (new KeyPairGenerator())->generateKeyPair();
        $base64Encoder = new Base64SafeEncoder();
        $keyParser = new KeyParser();
        $dataSigner = new DataSigner();
        $psrClient = new Psr18Client();

        $factory = new SecureHttpClientFactory(
            new HttpClient($psrClient, $psrClient, $psrClient),
            $base64Encoder,
            $keyParser,
            $dataSigner,
            new ServerErrorHandler()
        );

        $client = $factory->createSecureHttpClient($keyPair);

        $this->assertInstanceOf(SecureHttpClient::class, $client);
        $this->assertEquals($base64Encoder, $client->getBase64Encoder());
        $this->assertEquals($keyParser, $client->getKeyParser());
        $this->assertEquals($dataSigner, $client->getDataSigner());
        $this->assertEquals($keyPair, $client->getAccountKeyPair());
    }
}
