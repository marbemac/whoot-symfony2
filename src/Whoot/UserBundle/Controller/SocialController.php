<?php

namespace Whoot\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SocialController extends ContainerAware
{
    /**
     * 
     */
    public function friendsAction()
    {
        $response = new Response();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $facebook = $this->container->get('fos_facebook.api');
        $friends = $facebook->api(array(
                        "method"    => "fql.query",
                        "query"     => "SELECT uid,name FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me()) ORDER BY name ASC"
                    ));

        $notRegisteredFriends = array();
        foreach ($friends as $friend)
        {
            $notRegisteredFriends[$friend['uid']] = $friend;
        }
        $registeredFriends = $this->container->get('whoot.manager.user')->findUsersBy(array('status' => 'Active'), array('facebookId' => array_keys($notRegisteredFriends)));

        return $this->container->get('templating')->renderResponse(
            'WhootUserBundle:Social:friends.html.twig', array(
                'user' => $user,
                'registeredFriends' => $registeredFriends
            ), $response
        );
    }
}