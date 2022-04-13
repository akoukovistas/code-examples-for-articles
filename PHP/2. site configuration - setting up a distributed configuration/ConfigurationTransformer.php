<?php

namespace App\Transformers;

use App\Entity\Configuration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationTransformer
{
    /**
     * @param Configuration $configuration
     * @param string     $type
     *
     * @return array|JsonResponse|Response
     */
    public function transformConfiguration(Configuration $configuration, string $type = 'json')
    {

        $response = [
            'head' => json_decode($configuration->getHead(), true) ?? null,
            'nav' => json_decode($configuration->getNav(), true) ?? null,
            'options' => json_decode($configuration->getOptions(), true) ?? null,
            'footer' => json_decode($configuration->getFooter(), true) ?? null,
        ];

        if ($type === 'array') {
            return $response;
        }

        return $this->transformResponseType($response, $type);
    }

    /**
     * @param Configuration $configuration
     * @param string     $configType
     * @param string     $type
     *
     * @return array|JsonResponse|Response
     */
    public function transformSpecificConfig(Configuration $configuration, string $configType, string $type = 'json')
    {

        switch ($configType) {
            case 'head':
                $response = json_decode($configuration->getHead(), true) ?? null;
                break;
            case 'nav':
                $response = json_decode($configuration->getNav(), true) ?? null;
                break;
            case 'options' :
                $response = json_decode($configuration->getOptions(), true) ?? null;
                break;
            case 'footer':
                $response = json_decode($configuration->getFooter(), true) ?? null;
                break;
            case 'configuration':
                $response = $this->transformConfiguration($configuration, 'array');
                break;
            default:
                $response = ['error' => 'No config type found with this name - ' . $configType];
        }

        return $this->transformResponseType($response, $type);
    }

    /**
     * @param array  $response
     * @param string $type
     *
     * @return array|Response|JsonResponse
     */
    public function transformResponseType(array $response, string $type = 'json')
    {
        switch ($type) {
            case 'json':
                return new JsonResponse($response);
            case 'raw':
                return $response;
            default:
                return new JsonResponse(['error' => 'Cannot provide a response in the selected type']);
        }
    }
}