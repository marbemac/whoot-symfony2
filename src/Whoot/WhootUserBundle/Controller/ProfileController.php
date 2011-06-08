<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Limelight\LimelightUserBundle\Controller;

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
    public function showAction($username, $_format)
    {
        $templating = $this->container->get('templating');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $object = $this->container->get('limelight.user_manager')->findObjectBy(array('slug' => $username), array());

        return $templating->renderResponse('FOSUserBundle:Profile:show.html.twig', array('object' => $object, '_format' => $_format), $response);
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

        $object = $this->container->get('limelight.user_manager')->findObjectBy(array('id' => $id), array());

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

            return new RedirectResponse($this->container->get('router')->generate('fos_user_profile_show'));
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

        $object = $this->container->get('limelight.user_manager')->findObjectBy(array('id' => $id), array());

        return $this->container->get('templating')->renderResponse('LimelightUserBundle:Profile:tag.html.twig', array(
            'object' => $object
        ), $response);
    }

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }
}
