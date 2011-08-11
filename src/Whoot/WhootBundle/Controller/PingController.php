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

use Whoot\WhootBundle\Util\DateConverter;

class PingController extends ContainerAware
{
    /*
     * Pings/Unpings a user for the current user.
     *
     * @param integer $userId
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

        $pingAdded = $this->container->get('whoot.manager.ping')->addPing($this->container->get('security.context')->getToken()->getUser(), $userId, false);
        $result = $this->container->get('whoot.manager.user')->addPing($this->container->get('security.context')->getToken()->getUser(), $userId, true);

        if ($_format == 'json')
        {
            $result['status'] = 'success';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        if ($request->isXmlHttpRequest())
        {
            $result['userId'] = $userId;
            $result['event'] = 'ping_toggle';
            $result['flash'] = array('type' => 'success', 'message' => 'User pinged successfully!');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    /**
     * Display a ping tag for the current user.
     *
     * @param integer $toUser
     */
    public function tagAction($toUser)
    {
        $securityContext = $this->container->get('security.context');

        $countdown = null;
        if ($securityContext->isGranted('ROLE_USER'))
        {
            $fromUser = $securityContext->getToken()->getUser()->getId();
            $start = new DateConverter(null, 'Y-m-d 05:00:00', '-5 hours', $fromUser->getCurrentLocation()->getTimezone());
            $ping = $this->container->get('whoot.manager.ping')->findPing($fromUser, $toUser, $start);
            if ($ping)
            {
                $countdown = time() - $ping['createdAt']->getTimestamp();
                $countdown = $countdown <= 10 ? 10 - $countdown : null;
            }
        }
        else
        {
            $fromUser = null;
            $ping = null;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Ping:tag.html.twig', array(
            'fromUser' => $fromUser,
            'toUser' => $toUser,
            'countdown' => $countdown,
            'ping' => $ping
        ));
    }

}