<?php


namespace App\Controller\Api;

use App\Repository\ConfigurationRepository;
use App\Transformers\ConfigurationTransformer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/siteconfig")
 *
 * @Cache(expires="+5seconds", maxage="5", smaxage="5", public=true)
 *
 */
class ConfigurationController extends AbstractController
{
    /**
     * @Route("/get/{site}", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a site configuration file based on parameters"
     * )
     *
     * @SWG\Parameter(
     *     name="site",
     *     in="path",
     *     type="string",
     *     description="This is the site name the config is needed for"
     * )
     *
     * @SWG\Tag(name="siteconfig")
     * @param string $site
     * @param ConfigurationRepository $siteConfigRepository
     *
     * @return JsonResponse
     */
    public function getConfiguration(string $site, ConfigurationRepository $siteConfigRepository): JsonResponse
    {
        $siteConfigTransformer = new ConfigurationTransformer();

        if ($site) {
            $siteConfig = $siteConfigRepository->findOneBy(['name' => $site]);

            if ($siteConfig === null) {
                return $siteConfigTransformer->transformResponseType(
                    [
                        'error' => 'No Site Configuration has been found for this site.',
                    ],
                    'json'
                );
            }

            return $siteConfigTransformer->transformConfiguration($siteConfig, 'json');
        }

        return $siteConfigTransformer->transformResponseType(
            [
                'error' => 'No Site name has been provided',
            ],
            'json'
        );
    }

    /**
     * @Route("/get/{configType}/{site}", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns a site config based on parameters"
     * )
     *
     * @SWG\Parameter(
     *     name="configType",
     *     in="path",
     *     type="string",
     *     description="This is the config type that's requested"
     * )
     *
     * @SWG\Parameter(
     *     name="site",
     *     in="path",
     *     type="string",
     *     description="This is the site name the config is needed for"
     * )
     *
     * @SWG\Tag(name="siteconfig")
     * @param string $configType
     * @param string $site
     * @param ConfigurationRepository $siteConfigRepository
     *
     * @return JsonResponse
     */
    public function getSpecificConfig(
        string               $configType,
        string               $site,
        ConfigurationRepository $siteConfigRepository
    ): JsonResponse
    {
        $siteConfigTransformer = new ConfigurationTransformer();

        if (!$site && !$configType) {
            return $siteConfigTransformer->transformResponseType(
                ['error' => 'Parameter $site or $configType is not given'],
                'json'
            );
        }

        $siteConfig = $siteConfigRepository->findOneBy(['name' => $site]);

        if ($siteConfig === null) {
            return $siteConfigTransformer->transformResponseType(
                ['error' => 'No Config has been found for this site + type combination.'],
                'json'
            );
        }

        return $siteConfigTransformer->transformSpecificConfig($siteConfig, $configType, 'json');
    }
}