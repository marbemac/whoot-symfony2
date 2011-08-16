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

        $user = $this->container->get('security.context')->getToken()->getUser();
        $result = $this->container->get('whoot.manager.user')->toggleFollow($user, $userId);

        // Add/remove the notification
        if ($result['status'] == 'removed')
        {
            $this->container->get('marbemac.manager.notification')->removeNotification($userId, $user, 'Follow', null);
        }
        else
        {
            $this->container->get('marbemac.manager.notification')->addNotification($userId, null, null, 'Follow', $user, null);
        }

        if ($_format == 'json')
        {
            $result['newText'] = $result['status'] == 'removed' ? 'Follow' : 'Unfollow';
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
            $result['flash'] = array('type' => 'success', 'message' => 'User ' . ($result['status'] == 'removed' ? 'unfollowed' : 'followed') .' successfully!');
            $result['newText'] = $result['status'] == 'removed' ? 'Follow' : 'Unfollow';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}