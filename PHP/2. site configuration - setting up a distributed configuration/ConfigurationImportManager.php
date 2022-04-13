<?php


namespace App\Service\Configuration;

use App\Entity\Common\Domain;
use App\Entity\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ConfigurationImportManager
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface        $li
    )
    {
        $this->entityManager = $em;
        $this->logger = $li;
    }

    /**
     * @param array $configValues
     * @param string $siteName
     *
     * @return Configuration
     */
    public function processConfig(array $configValues, string $siteName): Configuration
    {
        $config = $this->entityManager->getRepository(Configuration::class)->findOneBy(['name' => $siteName]);
        if (empty($config)) {
            $config = new Configuration();
            $config->setName($siteName);
        }

        $domain = $this->getDomainEntity($configValues);
        $config->setDomain($domain);

        $config->setHead(json_encode($configValues['head']));
        $config->setNav(json_encode($configValues['nav']));
        $config->setOptions(json_encode($configValues['page']));
        $config->setFooter(json_encode($configValues['footer']));

        $this->entityManager->persist($config);
        $this->handleSeoFeedsImport($configValues);

        return $config;
    }

    /**
     * @param array|null $config
     *
     * @return Domain|null
     */
    private function getDomainEntity(?array $config): ?Domain
    {
        if (!empty($config['page']['siteId'])) {
            $domainRepository = $this->entityManager->getRepository(Domain::class);

            return $domainRepository->findOneBy(['siteId' => $config['page']['siteId']]);
        }

        return null;
    }

    public function saveToDB(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}