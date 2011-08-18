<?php

namespace Whoot\WhootBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CommentController extends ContainerAware
{
    /**
     * Show the new form
     */
    public function newAction($rootId=null, $type=null)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $templating = $this->container->get('templating');
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();

        $form = $this->container->get('whoot.form.comment');
        $formHandler = $this->container->get('whoot.form.handler.comment');

        $process = $formHandler->process(null, $user);

        if ($process === true) {
            $comment = $form->getData();
            $this->container->get('doctrine.odm.mongodb.document_manager')->detach($comment);
            $comment->setCreatedBy($user, true);

            // Add the notification
            if ($comment->getPost())
            {
                $target = $this->container->get('whoot.manager.post')->findPostBy(array('id' => $comment->getPost()));
                if ($target && $target->getCreatedBy() != $user->getId()->__toString())
                {
                    $this->container->get('marbemac.manager.notification')->addNotification($target->getCreatedBy(), $target->getId(), 'Post', null, null, 'Comment', $user, null, 'contributor');
                }
            }
            else if ($comment->getInvite() && $comment->getCreatedBy() != $user->getId()->__toString())
            {
                $target = $this->container->get('whoot.manager.invite')->findInviteBy(array('id' => $comment->getInvite()));
                if ($target && $target->getCreatedBy() != $user->getId()->__toString())
                {
                    $inviteUrl = $this->container->get('router')->generate('invite_show', array('inviteId' => $target->getId()->__toString()));
                    $this->container->get('marbemac.manager.notification')->addNotification($target->getCreatedBy(), $target->getId(), 'Invite', 'at '.$target->getVenue(), $inviteUrl, 'Comment', $user, null, 'contributor');
                }
            }

            // Send the email
            if ($target && $target->getCreatedBy() != $user->getId()->__toString())
            {
                $createdBy = $this->container->get('whoot.manager.user')->findUserBy(array('id' => new \MongoId($target->getCreatedBy())));
                $message = \Swift_Message::newInstance()
                    ->setSubject('New comment from '.$user->getFullName().' on the whoot')
                    ->setFrom('notifications@thewhoot.com')
                    ->setTo($createdBy->getEmail())
                    ->setBody($this->container->get('templating')->render('WhootBundle:Comment:email.html.twig', array('comment' => $comment, 'target' => $target, 'createdBy' => $createdBy)), 'text/html')
                    ->addPart($this->container->get('templating')->render('WhootBundle:Comment:email.txt.twig', array('comment' => $comment, 'target' => $target, 'createdBy' => $createdBy)), 'text/plain')
                ;
                $this->container->get('mailer')->send($message);
            }

            $flashMessage = 'Comment posted successfully!';
            $url = $this->container->get('router')->generate('homepage');

            if ($request->isXmlHttpRequest())
            {
                $result = array();
                $result['event'] = 'comment_created';
                $result['flash'] = array('type' => 'notice', 'message' => $flashMessage);
                $result['rootId'] = $comment->getPost() ? $comment->getPost()->__toString() : $comment->getInvite()->__toString();
                $result['comment'] = $templating->render('WhootBundle:Comment:teaser.html.twig', array('comment' => $comment));
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            $this->container->get('session')->setFlash('notice', $flashMessage);

            return new RedirectResponse($url);
        }

        if ($request->isXmlHttpRequest() && $this->container->get('request')->getMethod() == 'POST')
        {
            $result = array();
            $result['result'] = 'error';
            $result['form'] = $templating->render('WhootBundle:Comment:new.html.twig', array('form' => $form->createView(), 'comment' => $form->getData()));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $templating->renderResponse('WhootBundle:Comment:new.html.twig', array(
            'form' => $form->createView(),
            'rootId' => $rootId,
            'type' => $type
        ));
    }

    public function teaserAction($comment)
    {
        $user = $this->container->get('whoot.manager.user')->findUserBy(array('id' => new \MongoId($comment->getCreatedBy())));
        $this->container->get('doctrine.odm.mongodb.document_manager')->detach($comment);
        $comment->setCreatedBy($user, true);

        return $this->container->get('templating')->renderResponse('WhootBundle:Comment:teaser.html.twig', array(
            'comment' => $comment
        ));
    }
}