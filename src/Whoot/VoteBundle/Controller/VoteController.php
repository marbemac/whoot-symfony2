<?php

namespace Whoot\VoteBundle\Controller;

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

use Whoot\VoteBundle\Entity\Vote;

class VoteController extends ContainerAware
{
    /**
     * Show a vote box.
     * @param integer $objectId
     */
    public function showAction($objectId, $score=null, $theme=null, $_format='html')
    {
        $securityContext = $this->container->get('security.context');

        $response = new Response();
        $response->setCache(array(
        ));

        $user = $securityContext->getToken()->getUser();
        if ($securityContext->isGranted('ROLE_USER'))
        {
            $object = $this->container->get('whoot.manager.post')->findPostBy($objectId, null, null, null, true);

            // Error if the object does not exist.
            if (!$object)
            {
            }

            $vote = $this->container->get('whoot.manager.vote')->findVoteBy(array('post' => $objectId, 'voter' => $user->getId(), 'status' => 'Active'));
        }
        else
        {
            $vote = null;
        }

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            // return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootVoteBundle:Vote:show.'.$_format.'.twig', array(
            'objectId' => $objectId,
            'vote' => $vote,
            'score' => $score,
            'theme' => $theme
        ), $response);
    }

    /**
     * Register a vote on an object.
     *
     * @param string $objectType
     * @param integer $objectId
     * @param integer $amount
     *
     * @return redirect|json JSON format -> {'event': string, 'result': 'success|error|login', 'message': string, 'objectNewScore' => int, 'objectId': int, 'affectedUserNewScore': int, 'affectedUserId': int}
     */
    public function createAction($objectId, $amount)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $result = $this->container->get('whoot.manager.vote')->addVote('Post', $objectId, $amount);

        if ($request->isXmlHttpRequest())
        {
            if ($result['result'] == 'success')
            {
                $result['event'] = 'vote_toggle';
            }
            else
            {
                $result['flash'] = array('type' => 'error', 'message' => $result['message']);
            }

            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $this->container->get('session')->setFlash('error', $result['message']);

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}