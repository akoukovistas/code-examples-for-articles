<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Services\Whitelabel\RobotsService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RobotsController extends AbstractController
{
    /**
     * @Route("/robots.txt", name="robots")
     *
     * @Cache(expires="+30 minutes", maxage="1800", smaxage="1800", public=true)
     *
     * @param RobotsService $robotsService
     *
     * @return Response
     * @throws \App\Exceptions\RedirectException
     */
    public function robotsAction(RobotsService $robotsService): Response
    {
        return $robotsService->generateRobots();
    }
}