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

class FollowController extends ContainerAware
{
    /*
     * Follows/Unfollows a user for the current user.
     *
     * @param integer $objectId
     */
    public function toggleAction($userId, $_format='html')
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $userManager = $this->container->get('whoot.user_manager');
        $securityContext = $this->container->get('security.context');

        $result = $userManager->toggleFollow($this->container->get('security.context')->getToken()->getUser()->getId(), $userId);

        if ($_format == 'json')
        {
            $result['newText'] = $result['status'] == 'existing' ? 'Follow' : 'Unfollow';
            $result['status'] = 'success';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        if ($request->isXmlHttpRequest())
        {
            $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
            $result['feed'] = $feed->getContent();
            $result['userId'] = $userId;
            $result['event'] = 'follow_toggle';
            $result['flash'] = array('type' => 'success', 'message' => 'User ' . ($result['status'] == 'existing' ? 'unfollowed' : 'followed') .' successfully!');
            $result['newText'] = $result['status'] == 'existing' ? 'Follow' : 'Unfollow';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    /**
     * Display feed for objects a user is following.
     *
     * @param integer $userId
     * @param string $type All|User|Topic|List
     */
    public function showAction($userId, $type, $_format)
    {
        $types = $type == 'All' ? array('User', 'Topic', 'List') : array($type);
        $request = $this->container->get('request');
        $following = $this->container->get('limelight.object_manager')->findObjectsBy(array($userId), null, $types, null, array('Follow'), null);
        $userObject = $this->container->get('limelight.user_manager')->findObjectBy(array('id' => $userId));

        $followingObjects = array('User' => array(), 'Topic' => array());
        $objects = array();
        foreach ($following as $object)
        {
            $followingObjects[$object['nodeType']][] = $object;
            $objects[] = $object['id'];
        }

        return $this->container->get('templating')->renderResponse('LimelightBundle:Follow:show.html.twig', array(
            'objects' => $objects,
            'followingObjects' => $followingObjects,
            'userObject' => $userObject,
            'type' => $type,
            '_format' => $_format
        ));
    }

    /**
     * Display a follow tag for the current user.
     *
     * @param integer $toUser
     */
    public function tagAction($toUser)
    {
        $securityContext = $this->container->get('security.context');

        if ($securityContext->isGranted('ROLE_USER'))
        {
            $fromUser = $securityContext->getToken()->getUser()->getId();
            $connection = $this->container->get('whoot.user_manager')->findFollowConnection($fromUser, $toUser);
        }
        else
        {
            $fromUser = null;
            $connection = null;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Follow:tag.html.twig', array(
            'fromUser' => $fromUser,
            'toUser' => $toUser,
            'connection' => $connection
        ));
    }

}