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

        $user = $this->findUserBy('username', $username);

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

    public function followersAction($username)
    {
        $user = $this->container->get('whoot.user_manager')->getUser(array('username' => $username));
        $location = $this->container->get('whoot.user_manager')->getLocation($user->getZipcode());
        $followers = $this->container->get('whoot.user_manager')->getFollowers($user);

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:followers.html.twig', array(
            'user' => $user,
            'location' => $location,
            'followers' => $followers,
            'navSelected' => 'followers'
        ), $response);
    }

    public function followingAction($username)
    {
        $user = $this->container->get('whoot.user_manager')->getUser(array('username' => $username));
        $location = $this->container->get('whoot.user_manager')->getLocation($user->getZipcode());
        $following = $this->container->get('whoot.user_manager')->getFollowing($user);

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Profile:following.html.twig', array(
            'user' => $user,
            'location' => $location,
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

    public function searchAction()
    {
        return new Response();
    }

    /**
     * Find a user by a specific property
     *
     * @param string $key property name
     * @param mixed $value property value
     * @throws NotFoundException if user does not exist
     * @return User
     */
    protected function findUserBy($key, $value)
    {
        if (!empty($value)) {
            $user = $this->container->get('fos_user.user_manager')->{'findUserBy'.ucfirst($key)}($value);
        }

        if (empty($user)) {
            throw new NotFoundHttpException(sprintf('The user with "%s" does not exist for value "%s"', $key, $value));
        }

        return $user;
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
