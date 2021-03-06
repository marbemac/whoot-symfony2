<?php

namespace Whoot\WhootBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Whoot\WhootBundle\Util\DateConverter;

class CoreController extends ContainerAware
{
    public function headerAction($page=null)
    {
        $request = $this->container->get('request');

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Core:header.html.twig', array(
            'page' => $page
        ), $response);
    }

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

        if ($this->container->get('security.context')->isGranted('ROLE_USER'))
        {
            $myPost = $this->container->get('whoot.manager.post')->getMyPost($this->container->get('security.context')->getToken()->getUser());
        }
        else
        {
            $myPost = null;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Core:home.html.twig', array(
            'myPost' => $myPost        
        ), $response);
    }

    public function dealsAction()
    {
        $request = $this->container->get('request');

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        $myPost = $this->container->get('whoot.manager.post')->getMyPost($this->container->get('security.context')->getToken()->getUser());
        
        return $this->container->get('templating')->renderResponse('WhootBundle:Core:deals.html.twig', array(
            'myPost' => $myPost
        ), $response);
    }

    public function postBoxAction()
    {
        $request = $this->container->get('request');

        $myPost = $this->container->get('whoot.manager.post')->getMyPost($this->container->get('security.context')->getToken()->getUser());

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Core:postBox.html.twig', array('myPost' => $myPost), $response);
    }

    /*
     * Returns the html for feed filters.
     *
     * @param bool $showNodeTypes
     * @param bool $showFeedSort
     */
    public function feedFiltersAction($showPostTypes=true, $showFeedSort=true)
    {
        // Get the current user feed filters
        $session = $this->container->get('request')->getSession();
        $feedFilters = $session->get('feedFilters');
        if (!$feedFilters || !isset($feedFilters['postTypes']) || !isset($feedFilters['feedSort']) || !isset($feedFilters['timePeriod']))
            $reset = true;
        else
            $reset = false;

        // If this is the first request for feed filters or values are missing
        if ($reset)
        {
            $feedFilters['postTypes'] = isset($feedFilters['postTypes']) ? $feedFilters['postTypes'] : array('working', 'low_in', 'low_out', 'big_out');
            $feedFilters['feedSort'] = isset($feedFilters['feedSort']) ? $feedFilters['feedSort'] : 'score';
            $feedFilters['timePeriod'] = isset($feedFilters['timePeriod']) ? $feedFilters['timePeriod'] : 3;
            $session->set('feedFilters', $feedFilters);
        }

        $response = new Response();
        $response->setCache(array(
//            'etag'          => 5,
//            's_maxage'      => 60
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
//            return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Core:feed_filters.html.twig', array(
                    'feedFilters' => $feedFilters,
                    'showPostTypes' => $showPostTypes,
                    'showFeedSort' => $showFeedSort
                ), $response);
    }

    /*
     * Whoot stores the user feed settings in their session. This toggles a postType from showing in feeds.
     *
     * @param string $postType The post type to toggle.
     *
     * @return redirect|json
     */
    public function toggleFeedFilterAction($postType)
    {
        $session = $this->container->get('request')->getSession();
        $feedFilters = $session->get('feedFilters');
        $index = array_search($postType, $feedFilters['postTypes']);

        // Remove it if it's in there already
        if ($index !== false)
        {
            unset($feedFilters['postTypes'][$index]);
        }
        // Else we need to add it
        else
        {
            $feedFilters['postTypes'][] = $postType;
        }

        // Save the updated session
        $session->set('feedFilters', $feedFilters);

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            $result = array();
            $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
            $result['feed'] = $feed->getContent();
            $result['event'] = 'feed_filter_toggle';
            $result['result'] = 'success';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    /*
     * Whoot stores the user feed settings in their session. This changes a setting.
     *
     * @param string $key   The setting to change.
     * @param string $value The new setting value.
     *
     * @return redirect|json
     */
    public function changeFeedFilterAction($key, $value)
    {
        $session = $this->container->get('request')->getSession();
        $feedFilters = $session->get('feedFilters');
        $feedFilters[$key] = $value;

        // Save the updated session
        $session->set('feedFilters', $feedFilters);

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            $result = array();
            $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
            $result['feed'] = $feed->getContent();
            $result['event'] = 'feed_filter_change';
            $result['result'] = 'success';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    public function sidebarAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $since = new DateConverter(null, 'Y-m-d 05:00:00', '-5 hours', $user->getCurrentLocation() ? $user->getCurrentLocation()->getTimezone() : 'UTC');
        $undecidedUsers = $this->container->get('whoot.manager.user')->findUndecided($user, $since, 0, 0);
        $logoutUrl = $this->container->get('fos_facebook.helper')->getLogoutUrl();

        $response = new Response();
        $response->setCache(array(
//            'etag'          => 5,
//            's_maxage'      => 60
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
//            return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Core:sidebar.html.twig', array(
                    'undecidedUsers' => $undecidedUsers,
                    'logoutUrl' => $logoutUrl
                ), $response);
    }

    public function aboutAction()
    {
        return $this->container->get('templating')->renderResponse('WhootBundle:Core:about.html.twig', array());
    }

    public function termsAction()
    {
        return $this->container->get('templating')->renderResponse('WhootBundle:Core:terms.html.twig', array());
    }

    public function privacyAction()
    {
        return $this->container->get('templating')->renderResponse('WhootBundle:Core:privacy.html.twig', array());
    }
}