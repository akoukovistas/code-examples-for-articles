<?php


namespace App\Service\Configuration;

use App\Entity\Configuration;
use App\Transformers\ConfigurationTransformer;
use Doctrine\ORM\EntityManagerInterface;

class ConfigurationManager
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var ConfigurationTransformer
     */
    private ConfigurationTransformer $configTransformer;

    /**
     * ConfigurationManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param ConfigurationTransformer $configTransformer
     */
    public function __construct(EntityManagerInterface $em, ConfigurationTransformer $configTransformer)
    {
        $this->em = $em;
        $this->configTransformer = $configTransformer;
    }

    /**
     * @param string $site
     *
     * @return array
     */
    public function getConfiguration(string $site): array
    {
        $config = $this->em->getRepository(Configuration::class)->findOneBy(['name' => $site]);

        if ($config) {
            return $this->configTransformer->transformConfiguration($config, 'array');
        }

        return [];
    }
}