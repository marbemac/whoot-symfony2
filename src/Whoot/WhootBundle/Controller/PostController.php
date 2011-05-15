<?php

namespace Whoot\WhootBundle\Controller;

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

use Whoot\WhootBundle\Entity\Post;

class PostController extends ContainerAware
{
    /**
     * 
     */
    public function feedAction($postTypes=null, $feedSort=null)
    {
        $response = new Response();
        $feedFilters = $this->container->get('session')->get('feedFilters');

        $postTypes = !$postTypes ? $feedFilters['postTypes'] : $postTypes;
        $feedSort = !$feedSort ? $feedFilters['feedSort'] : $feedSort;

        $user = $this->container->get('security.context')->getToken()->getUser();

        // Don't even bother getting objects if we aren't including ANY node types
        if (empty($postTypes))
        {
            $posts = array();
        }
        else
        {
            $posts = $this->container->get('whoot.post_manager')->findPostsBy($user, $postTypes, $feedSort, date('Y-m-d 05:00:00', time()-(60*60*5)));
        }

        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:feed.html.twig', array(
            'myPost' => $myPost,
            'posts' => $posts,
        ), $response);
    }

    /**
     * Creates a new post for the day. Toggles if the user already has a post for today.
     */
    public function createAction($type)
    {
        $coreManager = $this->container->get('whoot.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $templating = $this->container->get('templating');

        $postResult = $this->container->get('whoot.post_manager')->togglePost($type, $this->container->get('security.context')->getToken()->getUser());

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
            $result['myPost'] = $templating->render('WhootBundle:Post:myPost.html.twig', array('myPost' => $postResult['post'], 'myPostPending' => null));
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
    public function teaserAction($postId, $cycleClass = '')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $post = $this->container->get('whoot.post_manager')->findPostBy($postId, null, null, 'Active', false);

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:teaser.html.twig', array(
            'post' => $post,
            'cycleClass' => $cycleClass
        ), $response);
    }

    /**
     * Display a jive tag for the current user.
     *
     * @param integer $postId
     */
    public function jiveTagAction($postId)
    {
        $securityContext = $this->container->get('security.context');

        if ($securityContext->isGranted('ROLE_USER'))
        {
            $fromUser = $securityContext->getToken()->getUser();
            $connection = $this->container->get('whoot.post_manager')->findJives($fromUser, $postId, 'Active', null, true);
        }
        else
        {
            $fromUser = null;
            $connection = null;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:jiveTag.html.twig', array(
            'fromUser' => $fromUser,
            'postId' => $postId,
            'connection' => $connection
        ));
    }

    /*
     * Toggles on/off a post invite request for the current user.
     *
     * @param integer $postId
     * @param bool $go If set to false will not excute if the user already has a pending invite.
     */
    public function jiveToggleAction($postId, $go)
    {
        $coreManager = $this->container->get('whoot.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $postManager = $this->container->get('whoot.post_manager');
        $result = $postManager->toggleJive($this->container->get('security.context')->getToken()->getUser(), $postId, true);

        if ($request->isXmlHttpRequest())
        {
            $result['postId'] = $postId;
            $result['event'] = 'jive_toggle';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}