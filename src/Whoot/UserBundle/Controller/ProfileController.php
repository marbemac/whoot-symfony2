<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Whoot\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the user profile
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ProfileController extends ContainerAware
{
    /**
     * Show the user
     */
    public function showAction($username)
    {
        $templating = $this->container->get('templating');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $user = $this->container->get('whoot.manager.user')->getUser(array('username' => $username));

        return $templating->renderResponse('FOSUserBundle:Profile:show.html.twig', array(
            'user' => $user,
            'navSelected' => 'feed'
        ), $response);
    }

    /**
     * Edit the user
     */
    public function editAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->container->get('fos_user.profile.form');
        $formHandler = $this->container->get('fos_user.profile.form.handler');

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash('fos_user_profile_updated', 'success');

            return new RedirectResponse($this->container->get('router')->generate('user_profile_show'));
        }

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Profile:edit.html.'.$this->container->getParameter('fos_user.template.engine'),
            array('form' => $form->createView(), 'theme' => $this->container->getParameter('fos_user.template.theme'))
        );
    }

    public function listAction()
    {
        $users = $this->container->get('whoot.manager.user')->getUsersBy($this->container->get('security.context')->getToken()->getUser());

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:list.html.twig', array(
            'users' => $users
        ));
    }

    public function followersAction($username=null, $offset=null, $limit=null, $_format='html')
    {
        if ($username)
        {
            $user = $this->container->get('whoot.manager.user')->findUserBy(array('username' => $username));
        }
        else
        {
            $user = $this->container->get('security.context')->getToken()->getUser();
        }
        
        $followers = $this->container->get('whoot.manager.user')->findUsersBy(array('following.'.$user->getId()->__toString() => $user->getId()), array(), $offset, $limit);

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:followers.'.$_format.'.twig', array(
            'user' => $user,
            'followers' => $followers,
            'navSelected' => 'followers'
        ), $response);
    }

    public function followingAction($username=null, $offset=null, $limit=null, $_format='html')
    {
        if ($username)
        {
            $user = $this->container->get('whoot.manager.user')->findUserBy(array('username' => $username));
        }
        else
        {
            $user = $this->container->get('security.context')->getToken()->getUser();
        }
        
        $following = $this->container->get('whoot.manager.user')->findUsersBy(array(), array('id' => $user->getFollowing()), $offset, $limit);

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:following.'.$_format.'.twig', array(
            'user' => $user,
            'following' => $following,
            'navSelected' => 'following'
        ), $response);
    }

    public function settingsAction($username)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($username != $user->getUsername())
        {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        $blocked_users = $this->container->get('whoot.manager.user')->findUsersBy(array('blocked_by' => $user->getId()));

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:settings.html.twig', array(
            'user' => $user,
            'navSelected' => 'settings',
            'blocked_users' => $blocked_users
        ), $response);
    }

    public function blockUserCreateAction()
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        $blockUserId = $this->container->get('request')->request->get('userId', null);
        if ($blockUserId)
        {
            $this->container->get('whoot.manager.user')->blockUser($user, $blockUserId);
        }

        $result = array();
        $result['result'] = 'success';
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

    public function blockUserDestroyAction()
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        $blockUserId = $this->container->get('request')->query->get('userId', null);

        if ($blockUserId)
        {
            $this->container->get('whoot.manager.user')->unblockUser($user, $blockUserId);
        }
        
        $result = array();
        $result['result'] = 'success';
        $result['event'] = 'user_unblocked';
        $result['flash'] = array('type' => 'success', 'message' => 'User successfully unblocked');
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function profileImageAction($w, $h)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user->getFacebookId())
        {
            // deal with fb image here
        }
        else
        {
            // Pull from gravatar
            $url = 'http://www.gravatar.com/avatar/'.md5( strtolower( trim( $user->getEmail() ) ) ).'?d=mm&s=500';
            $tmpLocation = '/tmp/'.uniqid('i');
            file_put_contents($tmpLocation, file_get_contents($url));
            $image = $this->container->get('marbemac.manager.image')->saveImage($tmpLocation, $user->getId(), null, 'user-profile', null, true, null, null, null);
        }

        $user->setCurrentProfileImage($image->getGroupId());
        $this->container->get('whoot.manager.user')->updateUser($user);

        $imageData = $this->container->get('marbemac.manager.image')->getImageUrlData($image->getGroupId(), $w, $h);

        return $this->container->get('http_kernel')->forward('MarbemacImageBundle:Image:show', array('imageData' => $imageData));
    }

    public function uploadPictureAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        $img = $_FILES['file']['tmp_name'];

        $image = $this->container->get('marbemac.manager.image')->saveImage($img, $user->getId(), null, 'user-profile', null, true, null, null, null);
        $user->setCurrentProfileImage($image->getGroupId());
        $this->container->get('whoot.manager.user')->updateUser($user);

        $result = array();
        $result['status'] = 'success';
        $result['imageUrl'] = $this->container->get('router')->generate('image_show', array(
                                                                                                'imageData' => $this->container->get('marbemac.manager.image')->getImageUrlData($image->getGroupId(), 65, 65)
                                                                                           )
        );
        echo json_encode($result);
        exit();
    }

    public function ajaxSearchAction($onlyFollowing)
    {
        $query = $this->container->get('request')->query->get('q');

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        if (!$query)
        {
            return $response;
        }

        $results = $this->container->get('whoot.manager.user')->findForSearch(
            $this->container->get('security.context')->getToken()->getUser(),
            $query,
            $onlyFollowing,
            10
        );

        // Add the images
        foreach ($results as $key => $result)
        {
            $results[$key]['formattedItem'] = $this->container->get('templating')->render('WhootUserBundle:Profile:search_result.html.twig', array(
                'data' => $result
            ));
        }

        $response->setContent(json_encode($results));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /*
     * The tab that is shown to users when hovering over a user name.
     */
    public function hoverTabAction($userId)
    {
        $response = new Response();
        $response->setCache(array(
//            'etag'          => 'user-hover-'.$userId,
            's_maxage'      => 60,
            'public'        => true
        ));

        // Check that the Response is not modified for the given Request
        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            return $response;
        }

        $user = $this->container->get('whoot.manager.user')->findUserBy(array('id' => new \MongoId($userId)));

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:hover_tab.html.twig', array(
            'user' => $user
        ), $response);
    }

    /*
     * Check to see if th user has filled in all required account info.
     * Show a collection screen if not.
     */
    public function collectInfoAction()
    {
        $response = new Response();
        $response->setCache(array(
        ));

        // Check that the Response is not modified for the given Request
        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
//            return $response;
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        $collect = array();
        if (!$user->getCurrentLocation())
        {
            $locations = $this->container->get('whoot.manager.location')->findLocationsBy(array('status' => 'Active'), array(), array('stateName', 'ASC'));
            $collect['locations'] = $locations;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:collect_info.html.twig', array(
            'collect' => $collect
        ), $response);
    }

    public function updateLocationAction()
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        $request = $this->container->get('request');
        $locationData = $request->request->get('location', null);
        if ($locationData)
        {
            $user = $this->container->get('whoot.manager.location')->updateCurrentLocation($user, $locationData);
            $this->container->get('whoot.manager.user')->updateUser($user);
        }

        $result = array();
        $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
        $result['feed'] = $feed->getContent();
        $result['result'] = 'success';
        $result['event'] = 'location_updated';
        $result['flash'] = array('type' => 'success', 'message' => 'Your location has been updated.');
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function searchAction()
    {
        return new Response();
    }

    /**
     * Get a user from the security context
     *
     * @throws AccessDeniedException if no user is authenticated
     * @return User
     */
    protected function getUser()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!$user) {
            throw new AccessDeniedException('A logged in user is required.');
        }

        return $user;
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param Boolean $reAuthenticate
     * @return null
     */
    protected function authenticateUser(User $user, $reAuthenticate = false)
    {
        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        if (true === $reAuthenticate) {
            $token->setAuthenticated(false);
        }

        $this->container->get('security.context')->setToken($token);
    }

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }
}
