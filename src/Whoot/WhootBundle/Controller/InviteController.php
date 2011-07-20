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

use Whoot\WhootBundle\Entity\Post;

class InviteController extends ContainerAware
{
    /**
     * 
     */
    public function feedAction($postTypes=null, $feedSort=null, $offset=0, $limit=null, $_format='html')
    {
        $response = new Response();
        $feedFilters = $this->container->get('session')->get('feedFilters');

        $postTypes = !$postTypes ? $feedFilters['postTypes'] : $postTypes;
        $feedSort = !$feedSort ? $feedFilters['feedSort'] : $feedSort;

        $user = $this->container->get('security.context')->getToken()->getUser();

        // Don't even bother getting objects if we aren't including ANY node types
        if (empty($postTypes))
        {
            $invites = array();
        }
        else
        {
            $invites = $this->container->get('whoot.manager.invite')->findInvitesBy($user, $postTypes, $feedSort, date('Y-m-d 05:00:00', time()-(60*60*5)), $offset, $limit);
        }

        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        if ($this->container->get('request')->isXmlHttpRequest())
        {
            return $this->container->get('templating')->renderResponse('WhootBundle:Invite:feed_content.html.twig', array(
                'invites' => $invites
            ), $response);
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Invite:feed.html.twig', array(
            'invites' => $invites
        ), $response);
    }

    /**
     * Creates a new invite for the day.
     */
    public function createAction($_format='html', $chromeless=false)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $templating = $this->container->get('templating');
        $request = $this->container->get('request');
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $form = $this->container->get('whoot.form.invite');
        $formHandler = $this->container->get('whoot.form.handler.invite');

        if ($formHandler->process(null, $user) === true) {
            $invite = $form->getData();

            if ($this->container->has('security.acl.provider')) {
                $provider = $this->container->get('security.acl.provider');
                $acl = $provider->createAcl(ObjectIdentity::fromDomainObject($invite));
                $acl->insertObjectAce(UserSecurityIdentity::fromAccount($user), MaskBuilder::MASK_OWNER);
                $provider->updateAcl($acl);
            }

            if ($_format == 'json')
            {
                $result = array();
                $result['result'] = 'success';
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            return new RedirectResponse($this->container->get('router')->generate('invite_show', array('inviteId' => $invite->getId())));
        }

        if ($chromeless)
        {
            return $templating->renderResponse('WhootBundle:Invite:new_content.html.twig', array(
                'form' => $form->createView(),
                '_format' => $_format
            ));
        }

        return $templating->renderResponse('WhootBundle:Invite:new.html.twig', array(
            'form' => $form->createView(),
            '_format' => $_format
        ));
    }

    /**
     * Get's the users current post and cancels it if they created it and it's an open invite
     */
    public function cancelAction()
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $myPost = $this->container->get('whoot.manager.post')->findPostBy(null, $this->container->get('security.context')->getToken()->getUser(), date('Y-m-d 05:00:00', time()-(60*60*5)), 'Active');
        if (!isset($myPost['invite']['id']) || $myPost['invite']['createdBy']['id'] != $this->container->get('security.context')->getToken()->getUser()->getId())
        {
            if ($request->isXmlHttpRequest())
            {
                $result = array();
                $result['result'] = 'error';
                $result['flash'] = array('type' => 'error', 'message' => 'You can only cancel your own open invites!');
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
        }

        $this->container->get('whoot.manager.invite')->cancelInvite($myPost['invite']['id']);

        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array());
            $result['feed'] = $feed->getContent();
            $result['result'] = 'success';
            $result['event'] = 'invite_cancelled';
            $result['flash'] = array('type' => 'success', 'message' => 'Your invite has been cancelled.');
            $myPost = $this->container->get('http_kernel')->forward('WhootBundle:Post:myPost', array());
            $result['myPost'] = $myPost->getContent();
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : $this->container->get('router')->generate('homepage'));
    }

    /**
     * {@inheritDoc}
     */
    public function showAction($inviteId, $_format='html')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $invite = $this->container->get('whoot.manager.invite')->findInviteBy($inviteId);
        $comments = $this->container->get('whoot.manager.comment')->findCommentsBy(null, $inviteId);

        return $this->container->get('templating')->renderResponse('WhootBundle:Invite:show.html.twig', array(
            'invite' => $invite,
            'comments' => $comments,
            '_format' => $_format
        ), $response);
    }

    public function teaserAction($inviteId, $_format='html')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $invite = $this->container->get('whoot.manager.invite')->findInviteBy($inviteId, null, null, 'Active', false);

        return $this->container->get('templating')->renderResponse('WhootBundle:Invite:teaser.'.$_format.'.twig', array(
            'invite' => $invite
        ), $response);
    }


    /**
     * Display a attending button for the current user.
     *
     * @param integer $inviteId
     */
    public function attendingButtonAction($inviteId, $_format='html')
    {
        $securityContext = $this->container->get('security.context');

        if ($securityContext->isGranted('ROLE_USER'))
        {
            $fromUser = $securityContext->getToken()->getUser();
            $post = $this->container->get('whoot.manager.post')->findPostBy(null, $fromUser, date('Y-m-d 05:00:00', time()-(60*60*5)), 'Active');
        }
        else
        {
            $fromUser = null;
            $post = null;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:Invite:attendingButton.'.$_format.'.twig', array(
            'fromUser' => $fromUser,
            'inviteId' => $inviteId,
            'post' => $post,
        ));
    }

    /*
     * Toggles on/off a invite attend for the current user.
     *
     * @param integer $postId
     */
    public function attendAction($inviteId, $_format='html')
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $user = $this->container->get('security.context')->getToken()->getUser();
        $postManager = $this->container->get('whoot.manager.post');

        // Check to see if this user is the creator of a currently active open invite
        $myPost = $postManager->findPostBy(null, $user, date('Y-m-d 05:00:00', time()-(60*60*5)), 'Active', true);
        if ($myPost->getInvite() && $myPost->getInvite()->getCreatedBy()->getId() == $user->getId())
        {
            $result = array();
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'You created an open invite. Cancel it first (button on right sidebar).');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $result = $this->container->get('whoot.manager.invite')->toggleAttending($user->getId(), $myPost, $inviteId);

        if ($_format == 'json')
        {
            $result = array('status' => 'success');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        if ($request->isXmlHttpRequest())
        {
            $myPost = $this->container->get('http_kernel')->forward('WhootBundle:Post:myPost', array());
            $result['myPost'] = $myPost->getContent();
            $result['inviteId'] = $inviteId;
            $result['event'] = 'attend_toggle';
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}