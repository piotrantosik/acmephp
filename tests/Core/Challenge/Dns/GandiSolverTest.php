<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\Dns\DnsDataExtractor;
use AcmePhp\Core\Challenge\Dns\GandiSolver;
use AcmePhp\Core\Http\HttpClient;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class GandiSolverTest extends TestCase
{
    public function testSupports()
    {
        $typeDns = 'dns-01';
        $typeHttp = 'http-01';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockClient = $this->prophesize(HttpClient::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);

        $solver = new GandiSolver($mockExtractor->reveal(), $mockClient->reveal());

        $stubChallenge->getType()->willReturn($typeDns);
        $this->assertTrue($solver->supports($stubChallenge->reveal()));

        $stubChallenge->getType()->willReturn($typeHttp);
        $this->assertFalse($solver->supports($stubChallenge->reveal()));
    }

    public function testSolve()
    {
        $domain = 'sub-domain.bar.com';
        $recordName = '_acme-challenge.sub-domain.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $mockHttpClient = $this->prophesize(HttpClient::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);
        $stubRequest = $this->prophesize(RequestInterface::class);
        $stubStream = $this->prophesize(StreamInterface::class);

        $solver = new GandiSolver($mockExtractor->reveal(), $mockHttpClient->reveal());
        $solver->configure(['api_key' => 'stub']);

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);
        $stubChallenge->getDomain()->willReturn($domain);

        $mockHttpClient->createRequest('PUT', 'https://dns.api.gandi.net/api/v5/domains/bar.com/records/_acme-challenge.sub-domain/TXT')->willReturn($stubRequest);
        $stubRequest->withHeader('X-Api-Key', 'stub')->shouldBeCalled()->willReturn($stubRequest);

        $mockHttpClient->createStream('{"rrset_type":"TXT","rrset_ttl":600,"rrset_name":"_acme-challenge.sub-domain","rrset_values":["record_value"]}')->shouldBeCalled()->willReturn($stubStream);
        $stubRequest->withBody($stubStream)->shouldBeCalled()->willReturn($stubRequest);

        $mockHttpClient->sendRequest($stubRequest)->shouldBeCalled();

        $solver->solve($stubChallenge->reveal());
    }

    public function testCleanup()
    {
        $domain = 'sub-domain.bar.com';
        $recordName = '_acme-challenge.sub-domain.bar.com.';
        $recordValue = 'record_value';

        $mockExtractor = $this->prophesize(DnsDataExtractor::class);
        $stubChallenge = $this->prophesize(AuthorizationChallenge::class);
        $mockHttpClient = $this->prophesize(HttpClient::class);
        $stubRequest = $this->prophesize(RequestInterface::class);

        $solver = new GandiSolver($mockExtractor->reveal(), $mockHttpClient->reveal());
        $solver->configure(['api_key' => 'stub']);

        $mockExtractor->getRecordName($stubChallenge->reveal())->willReturn($recordName);
        $mockExtractor->getRecordValue($stubChallenge->reveal())->willReturn($recordValue);
        $stubChallenge->getDomain()->willReturn($domain);

        $mockHttpClient->createRequest('DELETE', 'https://dns.api.gandi.net/api/v5/domains/bar.com/records/_acme-challenge.sub-domain/TXT')->willReturn($stubRequest);
        $stubRequest->withHeader('X-Api-Key', 'stub')->shouldBeCalled()->willReturn($stubRequest);

        $mockHttpClient->sendRequest($stubRequest)->shouldBeCalled();

        $solver->cleanup($stubChallenge->reveal());
    }
}
