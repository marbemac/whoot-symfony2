<?php

namespace Whoot\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserInviteController extends ContainerAware
{
    /**
     * 
     */
    public function createAction()
    {
        $response = new Response();

        $user = $this->container->get('security.context')->getToken()->getUser();
        $email = $this->container->get('request')->request->get('email', null);
        $result = array();

        // TODO: Factor out this email validation...
        // TODO: Move this out of the controller. Too fat!
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $targetUser = $this->container->get('whoot.manager.user')->findUserBy(array('email' => $email));
            if (!$targetUser)
            {
                $userInviteManager = $this->container->get('whoot.manager.user_invite');
                $userInvite = $userInviteManager->findUserInviteBy(array('email' => $email));

                if (!$userInvite)
                {
                    $userInvite = $userInviteManager->createUserInvite();
                    $userInvite->setEmail($email);
                }

                if (!$userInvite->hasInviter($user->getId()->__toString()))
                {
                    $userInvite->addInviter($user->getId()->__toString());
                    $userInviteManager->updateUserInvite($userInvite);

                    $mailer = $this->container->get('mailer');
                    $templating = $this->container->get('templating');
                    $message = \Swift_Message::newInstance()
                        ->setSubject($user->getFullName().' invited you to sign up on The Whoot!')
                        ->setFrom('hello@thewhoot.com')
                        ->setTo($email)
                        ->setBody($templating->render('WhootUserBundle:Invite:email.html.twig', array('inviter' => $user)), 'text/html')
                        ->addPart($templating->render('WhootUserBundle:Invite:email.txt.twig', array('inviter' => $user)), 'text/plain')
                    ;
                    $mailer->send($message);

                    $result['result'] = 'success';
                    $result['event'] = 'user_invited';
                    $result['email'] = $email;
                    $result['flash'] = array('type' => 'success', 'message' => $email.' successfully invited!');
                }
                else
                {
                    $result['result'] = 'error';
                    $result['flash'] = array('type' => 'error', 'message' => 'You\'ve already invited this friend!');
                }
            }
            else
            {
                $result = $this->container->get('whoot.manager.user')->toggleFollow($user, $targetUser->getId());
                $result['result'] = 'error';
                $result['flash'] = array('type' => 'error', 'message' => 'This user is already on the Whoot! You are now following '.$targetUser->getFullname());
            }
        }
        else
        {
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'You must provide a valid email address!');
        }

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
}