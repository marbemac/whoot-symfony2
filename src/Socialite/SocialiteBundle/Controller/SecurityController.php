<?php

namespace Socialite\SocialiteBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends ContainerAware
{
    /*
     * @param bool $chromeless
     */
    public function loginAction($chromeless=false)
    {
        // get the error if any (works with forward and redirect -- see below)
        if ($this->container->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $this->container->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $this->container->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
            $this->container->get('request')->getSession()->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }

        if ($this->container->get('request')->isXmlHttpRequest() || $chromeless)
        {
            return $this->container->get('templating')->renderResponse('SocialiteBundle:Security:login_content.html.twig', array(
                // last username entered by the user
                'last_username' => $this->container->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
                'error'         => $error,
            ));
        }

        return $this->container->get('templating')->renderResponse('SocialiteBundle:Security:login.html.twig', array(
            // last username entered by the user
            'last_username' => $this->container->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
            'error'         => $error
        ));
    }

    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
    }
}
