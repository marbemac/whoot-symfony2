<?php

namespace Whoot\WhootBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends ContainerAware
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

        return $this->container->get('templating')->renderResponse('WhootBundle:Admin:home.html.twig', array(
            'page' => 'home'
        ), $response);
    }

    public function trendingAction()
    {
        $response = new Response();

        $stopWords = $this->container->get('whoot.word_manager')->getTrending(null, null, null, array('isStopWord' => true));
        $trendableWords = $this->container->get('whoot.word_manager')->getTrending(null, null, null, array('trendable' => true));
        $uncategorizedWords = $this->container->get('whoot.word_manager')->getTrending(null, null, null, array('trendable' => false, 'isStopWord' => false));

        return $this->container->get('templating')->renderResponse('WhootBundle:Admin:trending.html.twig', array(
            'page' => 'trending',
            'trendableWords' => $trendableWords,
            'stopWords' => $stopWords,
            'uncategorizedWords' => $uncategorizedWords
        ), $response);
    }
}