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

class InviteController extends ContainerAware
{
    /**
     *
     */
    public function feedAction($postTypes=null, $feedSort=null, $offset=0, $limit=null, $_format='html')
    {
        $response = new Response();
        $feedFilters = $this->container->get('session')->get('feedFilters');

        $postTypes = $feedFilters['postTypes'];
        $feedSort = $feedFilters['feedSort'];

        $user = $this->container->get('security.context')->getToken()->getUser();

        // Don't even bother getting objects if we aren't including ANY node types
        if (empty($postTypes))
        {
            $invites = array();
        }
        else
        {
            $following = $user->getFollowing();
            $following[] = $user->getId();
            $start = new DateConverter(null, 'Y-m-d 05:00:00', '-5 hours', $user->getCurrentLocation() ? $user->getCurrentLocation()->getTimezone() : 'UTC');
            $invites = $this->container->get('whoot.manager.invite')->findInvitesBy(array('status' => 'Active'), array('type' => $postTypes, 'createdBy' => $following), array($feedSort => 'desc'), array('target' => 'createdAt', 'start' => $start), $offset, $limit);
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
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $form = $this->container->get('whoot.form.invite');
        $formHandler = $this->container->get('whoot.form.handler.invite');

        if ($formHandler->process(null, $user) === true) {
            $invite = $form->getData();

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

        $locations = $this->container->get('whoot.manager.location')->findLocationsBy(array('status' => 'Active'), array(), array('stateName', 'ASC'));

        if ($chromeless)
        {
            return $templating->renderResponse('WhootBundle:Invite:new_content.html.twig', array(
                'form' => $form->createView(),
                'locations' => $locations,
                '_format' => $_format
            ));
        }

        return $templating->renderResponse('WhootBundle:Invite:new.html.twig', array(
            'form' => $form->createView(),
            'locations' => $locations,
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

        $user = $this->container->get('security.context')->getToken()->getUser();
        $request = $this->container->get('request');
        $since = new DateConverter(null, 'Y-m-d 05:00:00', '-5 hours', $user->getCurrentLocation() ? $user->getCurrentLocation()->getTimezone() : 'UTC');
        $oldInvites = $this->container->get('whoot.manager.invite')->findInvitesBy(array('createdBy' => $user->getId(), 'status' => 'Active'), array(), array(), array('target' => 'createdAt', 'start' => $since));

        $invite = null;
        foreach ($oldInvites as $oldInvite)
        {
            $invite = $oldInvite;
            break;
        }

        if (!$invite)
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

        $this->container->get('whoot.manager.invite')->cancelInvite($invite);

        // Move all of the attendees to their old posts
        $attendees = $this->container->get('whoot.manager.user')->findUsersBy(array('status' => 'Active'), array('id' => $invite->getAttending()));
        $postManager = $this->container->get('whoot.manager.post');
        foreach ($attendees as $attendee)
        {
            $postManager->activateLastPost($attendee);
        }

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

        $invite = $this->container->get('whoot.manager.invite')->findInviteBy(array('id' => $inviteId));
        $user = $this->container->get('whoot.manager.user')->findUserBy(array('id' => $invite->getCreatedBy()));
        $attendees = $this->container->get('whoot.manager.user')->findUsersBy(array('status' => 'Active'), array('id' => $invite->getAttending()));
        $this->container->get('doctrine.odm.mongodb.document_manager')->detach($invite);
        $invite->setCreatedBy($user, true);
        $invite->setAttendees($attendees);
        $comments = $this->container->get('whoot.manager.comment')->findCommentsBy(array('invite' => $invite->getId(), 'status' => 'Active'));

        return $this->container->get('templating')->renderResponse('WhootBundle:Invite:show.html.twig', array(
            'invite' => $invite,
            'comments' => $comments,
            '_format' => $_format
        ), $response);
    }

    public function teaserAction($invite, $_format='html')
    {
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        $user = $this->container->get('whoot.manager.user')->findUserBy(array('id' => $invite->getCreatedBy()));
        $attendees = $this->container->get('whoot.manager.user')->findUsersBy(array('status' => 'Active'), array('id' => $invite->getAttending()));
        $this->container->get('doctrine.odm.mongodb.document_manager')->detach($invite);
        $invite->setCreatedBy($user, true);
        $invite->setAttendees($attendees);

        return $this->container->get('templating')->renderResponse('WhootBundle:Invite:teaser.'.$_format.'.twig', array(
            'invite' => $invite
        ), $response);
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

        // Check to see if this user is the creator of a currently active open invite
        $start = new DateConverter(null, 'Y-m-d 05:00:00', '-5 hours', $user->getCurrentLocation() ? $user->getCurrentLocation()->getTimezone() : 'UTC');
        $oldInvite = $this->container->get('whoot.manager.invite')->findInvitesBy(array('createdBy' => $user->getId(), 'status' => 'Active'), array(), array(), array('target' => 'createdAt', 'start' => $start));

        if (count($oldInvite) > 0)
        {
            $result = array();
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'You created an open invite. Cancel it first (button on right sidebar).');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $this->container->get('whoot.manager.post')->disableDailyPosts($user);
        $result = $this->container->get('whoot.manager.invite')->toggleAttendee($inviteId, $user);

        if ($result['action'] == 'add')
        {
            $this->container->get('whoot.manager.post')->setInvitePost($result['invite'], $user);
        }
        else
        {
            $this->container->get('whoot.manager.post')->activateLastPost($user);
        }

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