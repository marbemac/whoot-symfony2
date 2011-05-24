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
            $posts = $this->container->get('whoot.post_manager')->findPostsBy($user, $postTypes, $feedSort, date('Y-m-d 05:00:00', time()-(60*60*5)), false);
        }

        $undecidedUsers = $this->container->get('whoot.user_manager')->findUndecided($user, date('Y-m-d 05:00:00', time()-(60*60*5)));

        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            return $this->container->get('templating')->renderResponse('WhootBundle:Post:feed_content.html.twig', array(
                'posts' => $posts,
                'undecidedUsers' => $undecidedUsers
            ), $response);
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:feed.html.twig', array(
            'posts' => $posts,
            'undecidedUsers' => $undecidedUsers
        ), $response);
    }

    /**
     *
     */
    public function openInviteFeedAction($postTypes=null, $feedSort=null)
    {
        $response = new Response();
        $feedFilters = $this->container->get('session')->get('feedFilters');

        $postTypes = !$postTypes ? $feedFilters['postTypes'] : $postTypes;
        $feedSort = !$feedSort ? $feedFilters['feedSort'] : $feedSort;

        $user = $this->container->get('security.context')->getToken()->getUser();

        // Don't even bother getting objects if we aren't including ANY node types
        if (empty($postTypes))
        {
            $openInvites = array();
        }
        else
        {
            $openInvites = $this->container->get('whoot.post_manager')->findPostsBy($user, $postTypes, $feedSort, date('Y-m-d 05:00:00', time()-(60*60*5)), true);
        }

        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            return $this->container->get('templating')->renderResponse('WhootBundle:Post:open_invite_feed_content.html.twig', array(
                'openInvites' => $openInvites
            ), $response);
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:open_invite_feed.html.twig', array(
            'openInvites' => $openInvites
        ), $response);
    }

    public function myPostAction()
    {
        $request = $this->container->get('request');
        $myPost = $this->container->get('whoot.post_manager')->findMyPost($this->container->get('security.context')->getToken()->getUser(), 'Active');

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:myPost.html.twig', array('myPost' => $myPost), $response);
    }

    public function postBoxAction()
    {
        $request = $this->container->get('request');
        $myPost = $this->container->get('whoot.post_manager')->findMyPost($this->container->get('security.context')->getToken()->getUser(), 'Active');

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:postBox.html.twig', array('myPost' => $myPost), $response);
    }

    /**
     * Creates a new post for the day. Toggles if the user already has a post for today.
     */
    public function createAction()
    {
        $coreManager = $this->container->get('whoot.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $data['type'] = $request->request->get('type', 'working');
        $data['note'] = $request->request->get('note', '');
        $data['venue'] = $request->request->get('venue', '');
        $data['address'] = $request->request->get('address', '');
        $data['address_lat'] = $request->request->get('address_lat', '');
        $data['address_lon'] = $request->request->get('address_lon', '');
        $data['time'] = $request->request->get('time', '');
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();

        $postResult = $this->container->get('whoot.post_manager')->togglePost($data, $this->container->get('security.context')->getToken()->getUser());

        if ($this->container->has('security.acl.provider') && $postResult['status'] == 'new') {
            $provider = $this->container->get('security.acl.provider');
            $acl = $provider->createAcl(ObjectIdentity::fromDomainObject($postResult['post']));
            $acl->insertObjectAce(UserSecurityIdentity::fromAccount($user), MaskBuilder::MASK_OWNER);
            $provider->updateAcl($acl);
        }
        
        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
            $result['feed'] = $feed->getContent();
            $result['result'] = 'success';
            $result['event'] = 'post_created';
            $result['flash'] = array('type' => 'success', 'message' => 'Your status has been updated.');
            $myPost = $this->container->get('http_kernel')->forward('WhootBundle:Post:myPost', array());
            $result['myPost'] = $myPost->getContent();
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
        $activity = $this->container->get('whoot.post_manager')->buildActivity($post);

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:teaser.html.twig', array(
            'post' => $post,
            'activity' => $activity,
            'cycleClass' => $cycleClass
        ), $response);
    }

    public function openInviteTeaserAction($inviteId, $cycleClass = '')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $post = $this->container->get('whoot.post_manager')->findPostBy($inviteId, null, null, 'Active', false);
        $activity = $this->container->get('whoot.post_manager')->buildActivity($post);

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:openInviteTeaser.html.twig', array(
            'post' => $post,
            'activity' => $activity,
            'cycleClass' => $cycleClass
        ), $response);
    }

    public function openInviteDetailsAction($inviteId)
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $post = $this->container->get('whoot.post_manager')->findPostBy($inviteId, null, null, 'Active', false);
        $activity = $this->container->get('whoot.post_manager')->buildActivity($post);

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            $result = array();
            $result['status'] = 'success';
            $result['details'] = $this->container->get('templating')->render('WhootBundle:Post:openInviteDetails.html.twig', array(
                                    'post' => $post,
                                    'activity' => $activity
                                 ));

            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:openInviteDetails.html.twig', array(
            'post' => $post,
            'activity' => $activity,
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
            $post = $this->container->get('http_kernel')->forward('WhootBundle:Post:teaser', array('postId' => $postId));
            $result['post'] = $post->getContent();
            $myPost = $this->container->get('http_kernel')->forward('WhootBundle:Post:myPost', array());
            $result['myPost'] = $myPost->getContent();
            $result['postId'] = $postId;
            $result['event'] = 'jive_toggle';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}