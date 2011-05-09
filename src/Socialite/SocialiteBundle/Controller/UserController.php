<?php

namespace Socialite\SocialiteBundle\Controller;

use Socialite\SocialiteBundle\Entity\User;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\UserBundle\Controller\UserController as BaseUserController;

class UserController extends ContainerAware
{
    /**
     * Show the new form
     *
     * @param bool $chromeless
     */
    public function newAction($chromeless=false)
    {
        $request = $this->container->get('request');
        $username = uniqid();
        $form = $this->container->get('fos_user.form.user');
        $formHandler = $this->container->get('fos_user.form.handler.user');

        $process = $formHandler->process(null, $this->container->getParameter('fos_user.email.confirmation.enabled'));

        if ($process) {

            $user = $form->getData();

            if ($this->container->getParameter('fos_user.email.confirmation.enabled')) {
                $this->container->get('fos_user.mailer')->sendConfirmationEmailMessage($user, $this->getEngine());
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_user_check_confirmation_email';
            } else {
                $this->authenticateUser($user);
                $route = 'fos_user_user_confirmed';
            }
            
            if ($this->container->has('security.acl.provider')) {
                $provider = $this->container->get('security.acl.provider');
                $acl = $provider->createAcl(ObjectIdentity::fromDomainObject($user));
                $acl->insertObjectAce(UserSecurityIdentity::fromAccount($user), MaskBuilder::MASK_OWNER);
                $provider->updateAcl($acl);
            }
            
            $this->container->get('session')->setFlash('notice', 'Account created successfully!');
            $url = $this->container->get('router')->generate($route);
            
            if ($request->isXmlHttpRequest())
            {
                $response = new Response(json_encode(array('redirect' => $url)));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
            
            return new RedirectResponse($url);
        }
        
        if ($chromeless)
        {
            return $this->container->get('templating')->renderResponse('SocialiteBundle:User:new_content.html.twig', array('form' => $form->createView(), 'username' => $username));
        }
        
        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = 'error';
            $result['form'] = $this->container->get('templating')->render('SocialiteBundle:User:new_content.html.twig', array('form' => $form->createView(), 'username' => $username));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $this->container->get('templating')->renderResponse('SocialiteBundle:User:new.html.twig', array('form' => $form->createView(), 'username' => $username));
    }

    /**
     * {@inheritDoc}
     */
    public function showAction($username)
    {
        $request = $this->container->get('request');
        $templating = $this->container->get('templating');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $user = $this->findUserBy('username', $username);

        return $templating->renderResponse('SocialiteBundle:User:show.html.twig', array('user' => $user), $response);
    }
    
    /**
     * Tell the user his account is now confirmed
     */
    public function confirmedAction()
    {
        $user = $this->getUser();

        $this->container->get('session')->setFlash('success', 'Woot. Success.');
        return $this->container->get('templating')->renderResponse('SocialiteBundle:User:confirmed.html.twig', array(
            'user' => $user,
        ));
    }

    public function listAction()
    {
        $users = $this->container->get('socialite.user_manager')->getUsersBy($this->container->get('security.context')->getToken()->getUser());

        return $this->container->get('templating')->renderResponse('SocialiteBundle:User:list.html.twig', array(
            'users' => $users
        ));
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
        
        return $this->container->get('templating')->renderResponse('SocialiteBundle:User:teaser.html.twig', array(
            'object' => $object
        ), $response);
    }

    /**
     * {@inheritDoc}
     */
    public function tagAction($id)
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $object = $this->container->get('limelight.user_manager')->findObjectBy(array('id' => $id));
        
        return $this->container->get('templating')->renderResponse('SocialiteBundle:User:tag.html.twig', array(
            'object' => $object
        ), $response);
    }

    /**
     * Edit one user, show the edit form
     */
    public function editAction($username)
    {
        $user = $this->findUserBy('username', $username);
        $form = $this->container->get('fos_user.form.user');
        $formHandler = $this->container->get('fos_user.form.handler.user');

        $process = $formHandler->process($user);
        if ($process) {
            $this->container->get('session')->setFlash('notice', 'Account edited successfully!');
            $userUrl =  $this->container->get('router')->generate('fos_user_user_show', array('username' => $user->getUsername()));
            return new RedirectResponse($userUrl);
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:User:edit.html.'.$this->getEngine(), array(
            'form'      => $form->createView(),
            'username'  => $user->getUsername()
        ));
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
}