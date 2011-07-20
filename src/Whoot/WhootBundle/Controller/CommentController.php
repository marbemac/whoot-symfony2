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

use Whoot\WhootBundle\Entity\Comment;

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

        $process = $formHandler->process(null);

        if ($process === true) {
            $comment = $form->getData();

            if ($this->container->has('security.acl.provider'))
            {
                // creating the ACL
                $aclProvider = $this->container->get('security.acl.provider');
                $acl = $aclProvider->createAcl(ObjectIdentity::fromDomainObject($comment));

                // grant owner access
                $acl->insertObjectAce(UserSecurityIdentity::fromAccount($user), MaskBuilder::MASK_OWNER);
                $aclProvider->updateAcl($acl);
            }

            $flashMessage = 'Comment posted successfully!';
            $url = $this->container->get('router')->generate('homepage');

            if ($request->isXmlHttpRequest())
            {
                $result = array();
                $result['event'] = 'comment_created';
                $result['flash'] = array('type' => 'notice', 'message' => $flashMessage);
                $result['rootId'] = $comment->getPost() ? $comment->getPost()->getId() : $comment->getInvite()->getId();
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

    /**
     * {@inheritDoc}
     */
    public function editAction($id, $_format)
    {
        $coreManager = $this->container->get('limelight.core_manager');
        $login = $coreManager->mustLogin('xml');
        if ($login)
        {
            return $login;
        }

        $securityContext = $this->container->get('security.context');
        $object = $this->container->get('limelight.talk_manager')->findObjectBy(array('id' => $id), true);
        $denied = $coreManager->accessDenied('EDIT', $object, 'error', 'You do not have permission to edit this talk!');
        if ($denied)
        {
            return $denied;
        }

        $form = $this->container->get('limelight.form.talk');
        $form->process($object);

        return $this->container->get('templating')->renderResponse('LimelightBundle:Talk:edit.html.twig', array(
            'form'    => $form,
            'object'  => $object,
            '_format' => $_format
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function updateAction($id)
    {
        $coreManager = $this->container->get('limelight.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $object = $this->container->get('limelight.talk_manager')->findObjectBy(array('id' => $id), true);
        $form = $this->container->get('limelight.form.talk');
        $process = $form->process($object);
        if ($process) {
            $this->container->get('session')->setFlash('notice', 'Talk updated successfully!');
            $talkUrl =  $this->container->get('router')->generate('talk_show', array('id' => $object->getId(), 'slug' => $object->getSlug()));
            return new RedirectResponse($talkUrl);
        }

        $request = $this->container->get('request');
        $templating = $this->container->get('templating');
        $title = 'Edit Talk';

        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = 'error';
            $result['form'] = $templating->render('LimelightBundle:Talk:edit_content.html.twig', array('form' => $form, 'object' => $object));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $templating->renderResponse('LimelightBundle:Talk:edit.html.twig', array(
            'title' => $title,
            'form'      => $form,
            'object'  => $object
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAction($id)
    {
        $coreManager = $this->container->get('limelight.core_manager');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $object = $talkManager->findObjectBy(array('id' => $id), true);
        $denied = $coreManager->accessDenied('EDIT', $object, 'error', 'You do not have permission to delete this talk!');
        if ($denied)
        {
            return $denied;
        }

        $request = $this->container->get('request');
        $talkManager = $this->container->get('limelight.talk_manager');
        $result = $talkManager->deleteObject($object);

        if ($request->isXmlHttpRequest())
        {
            $result['event'] = 'talk_delete';
            $result['objectId'] = $id;
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $this->container->get('session')->setFlash('notice', 'Talk deleted successfully!');

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}