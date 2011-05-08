<?php

namespace Socialite\SocialiteBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Acl\Permission\MaskBuilder,
    Symfony\Component\Security\Acl\Domain\UserSecurityIdentity,
    Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Socialite\SocialiteBundle\Entity\Post;

class PostController extends ContainerAware
{
    /**
     * 
     */
    public function feedAction()
    {
        $request = $this->container->get('request');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }
        
        $myPost = $this->container->get('socialite.post_manager')->findTodaysPost($this->container->get('security.context')->getToken()->getUser());
        $posts = $this->container->get('socialite.post_manager')->findObjectsBy(array('status' => 'Active'));
        
        return $this->container->get('templating')->renderResponse('SocialiteBundle:Post:feed.html.twig', array(
            'myPost' => $myPost,
            'posts' => $posts,
        ), $response);
    }
    
    /**
     * Creates a new post for the day. Toggles if the user already has a post for today.
     */
    public function createAction($type)
    {
        $coreManager = $this->container->get('socialite.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $templating = $this->container->get('templating');

        $postResult = $this->container->get('socialite.post_manager')->togglePost($type, $this->container->get('security.context')->getToken()->getUser());
        
        if ($this->container->has('security.acl.provider') && $postResult['status'] == 'new') {
            $provider = $this->container->get('security.acl.provider');
            $acl = $provider->createAcl(ObjectIdentity::fromDomainObject($postResult['post']));
            $acl->insertObjectAce(UserSecurityIdentity::fromAccount($user), MaskBuilder::MASK_OWNER);
            $provider->updateAcl($acl);
        }
        
        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = 'success';
            $result['event'] = 'post_created';
            $result['flash'] = array('type' => 'success', 'message' => 'Your status has been updated.');
            $result['myPost'] = $templating->render('SocialiteBundle:Post:myPost.html.twig', array('myPost' => $postResult['post']));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : $this->container->get('router')->generate('homepage'));
    }

    /**
     * {@inheritDoc}
     */
    public function showAction($id, $_format)
    {
        $request = $this->container->get('request');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $object = $this->container->get('limelight.talk_manager')->findObjectBy(array('id' => $id));

        return $this->container->get('templating')->renderResponse('LimelightBundle:Talk:show.html.twig', array(
            'object' => $object,
            '_format' => $_format
        ), $response);
    }

    /**
     * {@inheritDoc}
     */
    public function teaserAction($id, $cycleClass = '')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $object = $this->container->get('limelight.talk_manager')->findObjectBy(array('id' => $id));

        // Get the current user feed filters
        $session = $this->container->get('request')->getSession();
        $feedFilters = $session->get('feedFilters');

        return $this->container->get('templating')->renderResponse('LimelightBundle:Talk:teaser.html.twig', array(
            'object' => $object,
            'feedType' => $feedFilters['feedType'],
            'cycleClass' => $cycleClass
        ), $response);
    }
}