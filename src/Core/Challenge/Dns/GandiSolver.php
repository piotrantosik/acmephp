<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\ConfigurableServiceInterface;
use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Http\HttpClient;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Webmozart\Assert\Assert;

/**
 * ACME DNS solver with automate configuration of a Gandi.Net.
 *
 * @author Alexander Obuhovich <aik.bold@gmail.com>
 */
class GandiSolver implements MultipleChallengesSolverInterface, ConfigurableServiceInterface
{
    use LoggerAwareTrait;

    /**
     * @var DnsDataExtractor
     */
    private $extractor;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    private $cacheZones;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct(DnsDataExtractor $extractor = null, HttpClient $httpClient)
    {
        $this->extractor = $extractor ?: new DnsDataExtractor();
        $this->httpClient = $httpClient;
        $this->logger = new NullLogger();
    }

    /**
     * Configure the service with a set of configuration.
     */
    public function configure(array $config)
    {
        $this->apiKey = $config['api_key'];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'dns-01' === $authorizationChallenge->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->solveAll([$authorizationChallenge]);
    }

    /**
     * {@inheritdoc}
     */
    public function solveAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);
            $recordValue = $this->extractor->getRecordValue($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $request = $this->httpClient->createRequest('PUT', 'https://dns.api.gandi.net/api/v5/domains/'.$topLevelDomain.'/records/'.$subDomain.'/TXT');
            $request = $request->withHeader('X-Api-Key', $this->apiKey);

            $data = [
                'rrset_type' => 'TXT',
                'rrset_ttl' => 600,
                'rrset_name' => $subDomain,
                'rrset_values' => [$recordValue],
            ];

            $request = $request->withBody($this->httpClient->createStream(\json_encode($data)));

            $this->httpClient->sendRequest($request);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->cleanupAll([$authorizationChallenge]);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        foreach ($authorizationChallenges as $authorizationChallenge) {
            $topLevelDomain = $this->getTopLevelDomain($authorizationChallenge->getDomain());
            $recordName = $this->extractor->getRecordName($authorizationChallenge);

            $subDomain = \str_replace('.'.$topLevelDomain.'.', '', $recordName);

            $request = $this->httpClient->createRequest('DELETE', 'https://dns.api.gandi.net/api/v5/domains/'.$topLevelDomain.'/records/'.$subDomain.'/TXT');
            $request = $request->withHeader('X-Api-Key', $this->apiKey);

            $this->httpClient->sendRequest($request);
        }
    }

    protected function getTopLevelDomain(string $domain): string
    {
        return \implode('.', \array_slice(\explode('.', $domain), -2));
    }
}
