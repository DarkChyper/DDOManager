<?php

namespace App\Services;

use App\Entity\DnsZone;
use App\Entity\Domain;
use App\Entity\Ip;
use App\Entity\SubDomain;
use App\Exceptions\SubDomainIdException;
use App\Repository\DnsZoneRepository;
use App\Repository\DomainRepository;
use App\Repository\IpRepository;
use App\Repository\SubDomainRepository;
use Ovh\Api;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DdomService implements LoggerAwareInterface
{
    public const API_OVH_DOMAIN_GET_DOMAIN_ZONE_EXPORT_URI = '/domain/zone/%s/export';
    public const API_OVH_DOMAIN_GET_DOMAIN_ZONE_RECORD_URI = '/domain/zone/%s/record';
    public const API_OVH_DOMAIN_GET_DOMAIN_ZONE_RECORD_ID_URI = '/domain/zone/%s/record/%s';
    public const API_OVH_DOMAIN_PUT_DOMAIN_ZONE_RECORD_ID_URI = '/domain/zone/%s/record/%s';
    public const API_OVH_DOMAIN_POST_DOMAIN_ZONE_REFRESH_URI = '/domain/zone/%s/refresh';

    private array $report;
    private ?SymfonyStyle $io;

    public function __construct(
        protected IpRepository $ipRepository,
        protected DnsZoneRepository $dnsZoneRepository,
        protected DomainRepository $domainRepository,
        protected SubDomainRepository $subDomainRepository,
        protected ParameterBagInterface $parameters,
        protected HttpClientInterface $client,
        protected Api $api,
        protected LoggerInterface $logger
    ) {
        $this->io = null;
        $this->report = [];
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function run(SymfonyStyle $io): void
    {
        $this->io = $io;

        $newIp = $this->getNewIp();
        $ipFromDb = $this->ipRepository->getLastIp();

        if (empty($ipFromDb) || $ipFromDb[0]->getLabel() !== $newIp->getLabel()) {
            $this->addLog('New IP, need to update DNS', 'info');

            $domains = $this->domainRepository->findAll();

            foreach ($domains as $domain) {
                $this->saveCurrentDnsZone($domain);
                $changes = false;

                /** @var SubDomain $subDomain */
                foreach ($domain->getSubDomains() as $subDomain) {
                    try {
                        if (is_null($subDomain->getApiId())) {
                            $subDomain->setApiId($this->getSubDomainId($domain->getLabel(), $subDomain->getLabel()));
                            $this->subDomainRepository->add($subDomain);
                        }
                        $dnsInfos = $this->getActualDnsAInfo($domain->getLabel(), $subDomain->getApiId());

                        if ($dnsInfos['target'] !== $newIp->getLabel()) {
                            $changes = true;
                            $dnsInfos['target'] = $newIp->getLabel();
                            $this->saveNewDnsAInfo($domain->getLabel(), $subDomain->getApiId(), $dnsInfos);
                        }
                    } catch (SubDomainIdException $subDomainIdException) {
                        $this->addLog($subDomainIdException->getMessage(), 'warning');
                    }
                }

                if ($changes) {
                    $this->refreshDnsZone($domain->getLabel());
                }
            }

            // save the new ip
            $this->ipRepository->add($newIp, true);
            $this->addLog('New ip \''.$newIp->getLabel().'\' saved at '.$newIp->getUpdateAt()->format('Y-m-d H:i:s'), 'info');
        } else {
            $this->addLog('Same IP, nothing to do. Last update at :'.$ipFromDb[0]->getUpdateAt()->format('Y-m-d H:i:s'), 'info');
        }

        // TODO send a notif somewhere
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    protected function getNewIp(): Ip
    {
        $response = $this->client->request('GET', $this->parameters->get('get_ip_api_url'));

        if (200 !== $response->getStatusCode()) {
            throw new HttpException('Unable to retrieve current IP');
        }
        $datas = json_decode($response->getContent(), true);

        $newIp = new Ip();

        return $newIp
            ->setLabel($datas['ips'][0])
            ->setCheckedAt(new \DateTime())
            ->setUpdateAt(new \DateTimeImmutable());
    }

    protected function saveCurrentDnsZone(Domain $domain): void
    {
        $uri = sprintf(self::API_OVH_DOMAIN_GET_DOMAIN_ZONE_EXPORT_URI, $domain->getLabel());
        $content = (string) $this->api->get($uri);

        $newDnsZone = new DnsZone();
        $newDnsZone
            ->setDomain($domain)
            ->setContent($content)
            ->setSavedAt(new \DateTimeImmutable());

        $this->dnsZoneRepository->add($newDnsZone, true);
    }

    protected function getSubDomainId(string $domainLabel, string $subDomainLabel): string
    {
        $uri = sprintf(self::API_OVH_DOMAIN_GET_DOMAIN_ZONE_RECORD_URI, $domainLabel);
        $content = $this->api->get($uri.'?fieldType=A&subDomain='.$subDomainLabel);

        if (empty($content)) {
            throw new SubDomainIdException('Subdomain \''.$subDomainLabel.'\' from domain \''.$domainLabel.'\. is unknown from OVH API.');
        }

        return $content[0];
    }

    protected function getActualDnsAInfo(string $domainLabel, string $subDomainId): array
    {
        $uri = sprintf(self::API_OVH_DOMAIN_GET_DOMAIN_ZONE_RECORD_ID_URI, $domainLabel, $subDomainId);

        return $this->api->get($uri);
    }

    protected function saveNewDnsAInfo(string $domainLabel, string $subDomainId, array $dnsInfos): void
    {
        $uri = sprintf(self::API_OVH_DOMAIN_PUT_DOMAIN_ZONE_RECORD_ID_URI, $domainLabel, $subDomainId);
        $this->api->put($uri, $dnsInfos);
    }

    protected function refreshDnsZone(string $domainLabel): void
    {
        $uri = sprintf(self::API_OVH_DOMAIN_POST_DOMAIN_ZONE_REFRESH_URI, $domainLabel);
        $this->api->post($uri);
    }

    private function addLog(string $message, string $level): void
    {
        $currentDateTime = new \DateTime();

        if (!is_null($this->io)) {
            $this->io->text($currentDateTime->format('Y-m-d H:i:s').' : '.$message);
        }

        $this->logger->log($level, $message);

        $this->report[] = [
            'datetime' => $currentDateTime->format('Y-m-d H:i:s'),
            'message' => $message,
            'level' => $level,
        ];
    }
}
