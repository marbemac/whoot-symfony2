<?php

namespace Socialite\SocialiteBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;

class CoreController extends ContainerAware
{
    public function homeAction()
    {
        $request = $this->container->get('request');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('SocialiteBundle:Core:home.html.twig', array(), $response);
    }
}