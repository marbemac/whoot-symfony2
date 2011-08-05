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

use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Model\UserInterface;

/**
 * Controller managing the registration
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RegistrationController extends ContainerAware
{
    public function registerAction($_format, $chromeless=false)
    {
        $request = $this->container->get('request');
        $username = uniqid();
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('fos_user.registration.form.handler');

        $process = $formHandler->process($this->container->getParameter('fos_user.registration.confirmation.enabled'));
        if ($process) {
            $user = $form->getData();

            if ($this->container->getParameter('fos_user.registration.confirmation.enabled')) {
                $this->container->get('fos_user.mailer')->sendConfirmationEmailMessage($user);
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_registration_check_email';
            } else {
                $this->authenticateUser($user);
                $route = 'fos_user_registration_confirmed';
            }

            $rockstars = $this->container->get('whoot.manager.user')->findUsersBy(array(), array('email' => array('marbemac@gmail.com', 'wquartner@gmail.com', 'rcoyne1022@gmail.com')));
            foreach ($rockstars as $rockstar)
            {
                $result = $this->container->get('whoot.manager.user')->toggleFollow($user, $rockstar->getId());
            }
            
            $this->container->get('session')->setFlash('notice', 'Account created successfully!');
            $url = $this->container->get('router')->generate($route);

            if ($_format == 'json')
            {
                $response = new Response(json_encode(array('status' => 'success')));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            if ($request->isXmlHttpRequest())
            {
                $response = new Response(json_encode(array('redirect' => $url)));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            return new RedirectResponse($url);
        }

        if ($_format == 'json')
        {
            $result = array();
            $result['status'] = 'error';
            foreach ($form->getChildren() as $child)
            {
                if ($child->hasErrors())
                {
                    $result['errors'][] = $child->getErrors();
                }
            }
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        if ($chromeless)
        {
            return $this->container->get('templating')->renderResponse('WhootUserBundle:Registration:register_content.html.twig', array('form' => $form->createView(), 'username' => $username));
        }

        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = 'error';
            $result['form'] = $this->container->get('templating')->render('WhootUserBundle:Registration:register_content.html.twig', array('form' => $form->createView(), 'username' => $username));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootUserBundle:Registration:register.html.twig', array('form' => $form->createView(), 'username' => $username));
    }

    /**
     * Tell the user his account is now confirmed
     */
    public function confirmedAction($_format)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('You do not have access to this section.');
        }

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            $url = $this->container->get('router')->generate('user_registration_confirmed');
            $response = new Response(json_encode(array('reload' => $url)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:confirmed.html.twig', array(
            'user' => $user,
            '_format' => $_format
        ));
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param Boolean $reAuthenticate
     */
    protected function authenticateUser(UserInterface $user)
    {
        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
    }
}
