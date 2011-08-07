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
    public function feedAction($list=null, $offset=0, $limit=null, $_format='html')
    {
        $response = new Response();
        $feedFilters = $this->container->get('session')->get('feedFilters');

        $postTypes = $feedFilters['postTypes'];
        $feedSort = $feedFilters['feedSort'];

        $user = $this->container->get('security.context')->getToken()->getUser();

        // Don't even bother getting objects if we aren't including ANY node types
        if (empty($postTypes))
        {
            $posts = array();
        }
        else if ($list)
        {
            $posts = $this->container->get('whoot.manager.post')->findPostsBy(array('isCurrentPost' => true), array('createdBy' => $list->getUsers()), array($feedSort => 'desc'), array('target' => 'createdAt', 'start' => date('Y-m-d 05:00:00', time()-(60*60*5))), $offset, $limit);
        }
        else
        {
            $following = $user->getFollowing();
            $following[] = $user->getId();
            $posts = $this->container->get('whoot.manager.post')->findPostsBy(array('isCurrentPost' => true), array('type' => $postTypes, 'createdBy' => $following), array($feedSort => 'desc'), array('target' => 'createdAt', 'start' => date('Y-m-d 05:00:00', time()-(60*60*5))), $offset, $limit);
        }

        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        // Used by the API
        if ($_format == 'json')
        {
            return $this->container->get('templating')->renderResponse('WhootBundle:Post:feed.'.$_format.'.twig', array(
                'posts' => $posts
            ), $response);
        }

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            return $this->container->get('templating')->renderResponse('WhootBundle:Post:feed_content.html.twig', array(
                'posts' => $posts
            ), $response);
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:feed.html.twig', array(
            'posts' => $posts
        ), $response);
    }

    public function undecidedAction($offset=0, $limit=null, $_format='html')
    {
        $response = new Response();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $undecidedUsers = $this->container->get('whoot.manager.user')->findUndecided($user, date('Y-m-d 05:00:00', time()-(60*60*5)), null, $offset, $limit);

        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        // Used by the API
        return $this->container->get('templating')->renderResponse('WhootBundle:Post:undecided.'.$_format.'.twig', array(
            'users' => $undecidedUsers
        ), $response);
    }

    public function myPostAction($_format='html')
    {
        $request = $this->container->get('request');
        $myPost = $this->container->get('whoot.manager.post')->getMyPost($this->container->get('security.context')->getToken()->getUser());

        if ($myPost && $myPost->getInvite())
        {
            $invite = $this->container->get('whoot.manager.invite')->findInviteBy(array('id' => $myPost->getInvite()->getInvite()));
            if ($invite)
            {
                $this->container->get('doctrine.odm.mongodb.document_manager')->detach($myPost);
                $this->container->get('doctrine.odm.mongodb.document_manager')->detach($invite);

                $createdBy = $this->container->get('whoot.manager.user')->findUserBy(array('id' => new \MongoId($invite->getCreatedBy())));
                $invite->setCreatedBy($createdBy, true);
                $myPost->setInvite($invite, true);
            }
        }

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:myPost.'.$_format.'.twig', array('myPost' => $myPost), $response);
    }

    /**
     * Creates a new post for the day. Toggles if the user already has a post for today.
     */
    public function createAction($_format='html')
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $templating = $this->container->get('templating');
        $request = $this->container->get('request');
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $form = $this->container->get('whoot.form.post');
        $formHandler = $this->container->get('whoot.form.handler.post');

        if ($formHandler->process(null, $user) === true) {
            $post = $form->getData();

            if ($_format == 'json')
            {
                $result = array();
                $result['result'] = 'success';
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            if ($request->isXmlHttpRequest())
            {
                $result = array();
                $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
                $result['feed'] = $feed->getContent();
                $result['result'] = 'success';
                $result['event'] = 'post_created';
                $result['flash'] = array('type' => 'success', 'message' => 'Your post has been updated.');
                $myPost = $this->container->get('http_kernel')->forward('WhootBundle:Post:myPost', array());
                $result['myPost'] = $myPost->getContent();
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
        }

        $locations = $this->container->get('whoot.manager.location')->findLocationsBy(array('status' => 'Active'), array(), array('stateName', 'ASC'));

        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = 'error';
            $result['form'] = $templating->render('WhootBundle:Post:new.html.twig', array('form' => $form->createView(), 'locations' => $locations));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $templating->renderResponse('WhootBundle:Post:new.html.twig', array(
            'form' => $form->createView(),
            'locations' => $locations,
            '_format' => $_format
        ));
    }

    public function teaserAction($post, $_format='html')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $user = $this->container->get('whoot.manager.user')->findUserBy(array('id' => $post->getCreatedBy()));
        $this->container->get('doctrine.odm.mongodb.document_manager')->detach($post);
        $post->setCreatedBy($user, true);

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:teaser.'.$_format.'.twig', array(
            'post' => $post
        ), $response);
    }

    public function detailsAction($postId, $_format='html')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $post = $this->container->get('whoot.manager.post')->findPostBy(array('id' => $postId));
        $voters = $this->container->get('whoot.manager.user')->findUsersBy(array('status' => 'Active'), array('id' => $post->getVotes()));
        $post->setVoters($voters);

        $comments = $this->container->get('whoot.manager.comment')->findCommentsBy(array('post' => $post->getId(), 'status' => 'Active'));

        if ($_format == 'json')
        {
            return $this->container->get('templating')->renderResponse('WhootBundle:Post:teaser.json.twig', array(
                'post' => $post,
                'comments' => $comments,
                'detailed' => true
            ), $response);
        }

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            $result = array();
            $result['status'] = 'success';
            $result['details'] = $this->container->get('templating')->render('WhootBundle:Post:details.html.twig', array(
                                    'post' => $post,
                                    'comments' => $comments
                                 ));

            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Post:details.html.twig', array(
            'post' => $post,
            'comments' => $comments
        ), $response);
    }
}