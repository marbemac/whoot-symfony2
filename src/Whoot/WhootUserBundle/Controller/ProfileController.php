<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Whoot\WhootUserBundle\Controller;

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

        $user = $this->container->get('whoot.user_manager')->getUser(array('username' => $username));

        return $templating->renderResponse('FOSUserBundle:Profile:show.html.twig', array(
            'user' => $user,
            'navSelected' => 'feed'
        ), $response);
    }

    /**
     * {@inheritDoc}
     */
    public function teaserAction($id)
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $object = $this->container->get('limelight.user_manager')->findObjectBy(array('id' => $id));

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Profile:teaser.html.twig', array(
            'object' => $object
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

    public function tagAction($id)
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $object = $this->container->get('whoot.user_manager')->findObjectBy(array('id' => $id), array());

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:tag.html.twig', array(
            'object' => $object
        ), $response);
    }

    public function listAction()
    {
        $users = $this->container->get('whoot.user_manager')->getUsersBy($this->container->get('security.context')->getToken()->getUser());

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:list.html.twig', array(
            'users' => $users
        ));
    }

    public function followersAction($username=null, $_format='html')
    {
        if ($username)
        {
            $user = $this->container->get('whoot.user_manager')->getUser(array('username' => $username));
        }
        else
        {
            $user = $this->container->get('security.context')->getToken()->getUser();
        }
        
        $followers = $this->container->get('whoot.user_manager')->getFollowers($user);

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

    public function followingAction($username=null, $_format='html')
    {
        if ($username)
        {
            $user = $this->container->get('whoot.user_manager')->getUser(array('username' => $username));
        }
        else
        {
            $user = $this->container->get('security.context')->getToken()->getUser();
        }
        
        $following = $this->container->get('whoot.user_manager')->getFollowing($user);

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
        $location = $this->container->get('whoot.user_manager')->getLocation($user->getZipcode());

        if ($username != $user->getUsername())
        {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:settings.html.twig', array(
            'user' => $user,
            'location' => $location,
            'navSelected' => 'settings'
        ), $response);
    }

    public function uploadPictureAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        $img = $_FILES['file']['tmp_name'];

        // check that it's an image
        $fileTypes = array('jpg','jpeg','gif','png'); // File extensions
        $imgInfo_array = getimagesize($img);
        $parts = explode('/', $imgInfo_array['mime']);
        $ext = $parts[count($parts)-1];

        $result = array();

        if ($img > 5000000 || !in_array($ext,$fileTypes))
        {
            $result['status'] = 'error';
        }
        else
        {
            $filename = uniqid('UI') . '.' . $ext;
            $filepath = $_SERVER['DOCUMENT_ROOT'].'/uploads/user_profile_images/'.$filename;
            $webpath = '/uploads/user_profile_images/'.$filename;
            move_uploaded_file($img, $filepath);
            $result['status'] = 'success';
            $result['filename'] = $filename;
            $result['filepath'] = $webpath;
            $user->setProfileImage($webpath);
            $this->container->get('doctrine')->getEntityManager()->persist($user);
            $this->container->get('doctrine')->getEntityManager()->flush();
        }

        return 'For some reason returning a json response screws things up...';

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function ajaxSearchAction()
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

        $results = $this->container->get('whoot.user_manager')->findForSearch(
            $this->container->get('security.context')->getToken()->getUser()->getId(),
            $query
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
    public function hoverTabAction($id)
    {
        $response = new Response();
        $response->setCache(array(
            'etag'          => 'user-hover-'.$id,
            's_maxage'      => 300,
            'public'        => true
        ));

        // Check that the Response is not modified for the given Request
        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            return $response;
        }

        $user = $this->container->get('whoot.user_manager')->getUser(array('id' => $id), false);

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
        if (!$user->getLocation())
        {
            $locations = $this->container->get('whoot.location_manager')->findLocationsBy(array());
            $collect['location'] = $locations;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:collect_info.html.twig', array(
            'collect' => $collect
        ), $response);
    }

    public function updateLocationAction()
    {
        $coreManager = $this->container->get('whoot.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $location = $request->request->get('location', null);
        $location = $this->container->get('whoot.location_manager')->findLocationBy(array('id' => $location), true);
        if (!$location)
            return null;

        $user = $this->container->get('security.context')->getToken()->getUser();
        $user->setLocation($location);
        $this->container->get('whoot.user_manager')->updateUser($user);

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
