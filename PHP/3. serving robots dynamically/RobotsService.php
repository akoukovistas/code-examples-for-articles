<?php

namespace App\Services\Whitelabel;

use App\Exceptions\RedirectException;
use App\Services\DataSource;
use Symfony\Component\HttpFoundation\Response;

class RobotsService {

    /**
     * @var DataSource
     */
    private DataSource $dataSource;

    /**
     * @var Response
     */
    private Response $robotsResponse;

    /**
     * @var array
     */
    private array $config;

    /**
     * RobotsService constructor.
     *
     * @param DataSource        $dataSource
     */
    public function __construct(DataSource $dataSource)
    {
        $this->dataSource = $dataSource;

        $this->robotsResponse = new Response();
    }

    /**
     * @return Response
     * @throws RedirectException
     */
    public function generateRobots(): Response
    {

        $this->getConfig();
        $this->robotsResponse->setContent($this->generateRobotsContent());
        $this->robotsResponse->headers->set('Content-Type', 'text/plain');
        $this->robotsResponse->headers->set('Content-Length', strlen($this->robotsResponse->getContent()));

        return $this->robotsResponse;
    }

    /**
     * @throws RedirectException
     */
    private function getConfig(): void
    {
        $config = $this->dataSource->getData('foobar');
        if (!empty($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * @param array $config
     * @return void
     */
    private function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    private function generateRobotsContent(): string
    {
        $content = '';
        $robotsConfig = $this->config['foo']['robots'] ?? [];

        if (is_array($robotsConfig)) {
            $lastDirective = '';
            foreach ($robotsConfig as $index => $robotsLine) {
                // Need to check the types and structure to make sure we don't get random errors from formatting issues.
                if (is_array($robotsLine) && count($robotsLine) === 2 && is_string($robotsLine[0]) && is_string(
                        $robotsLine[1]
                    )) {
                    // There must be a newline before each user-agent and for the sitemaps but the sitemaps need to be stuck together.
                    if (($robotsLine[0] === 'User-agent' && $index > 0) || ($robotsLine[0] === 'Sitemap' && $lastDirective !== 'Sitemap')) {
                        $content .= PHP_EOL;
                    }
                    $content .= sprintf("%s: %s%s", $robotsLine[0], $robotsLine[1], PHP_EOL);
                    $lastDirective = $robotsLine[0];
                }
            }
        }

        return $content;
    }
}