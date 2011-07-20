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

use Whoot\WhootBundle\Entity\LList;
use Whoot\WhootBundle\Form\ListForm;

class LListController extends ContainerAware
{
    /*
     * Get a list of lists.
     *
     * @param integer $userId User object_id
     */
    public function listAction($userId)
    {
        $lists = $this->container->get('whoot.manager.list')->findListsBy(array('createdBy' => $userId, 'status' => 'Active'));

        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($this->container->get('request'))) {
            // return the 304 Response immediately
            //return $response;
        }

        return $this->container->get('templating')->renderResponse('WhootBundle:LList:list.html.twig', array('lists' => $lists), $response);
    }

    /**
     * {@inheritDoc}
     */
    public function newAction($chromeless=null)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $securityContext = $this->container->get('security.context');
        $templating = $this->container->get('templating');

        $form = $this->container->get('whoot.form.list');
        $formHandler = $this->container->get('whoot.form.handler.list');

        $process = $formHandler->process(null);
        if ($process) {
            $list = $form->getData();
            $redirectUrl = $this->container->get('router')->generate('homepage');
            $flashMessage = 'List created successfully!';

            if ($this->container->has('security.acl.provider'))
            {
                // creating the ACL
                $aclProvider = $this->container->get('security.acl.provider');
                $acl = $aclProvider->createAcl(ObjectIdentity::fromDomainObject($list));

                // grant owner access
                $acl->insertObjectAce(UserSecurityIdentity::fromAccount($securityContext->getToken()->getUser()), MaskBuilder::MASK_OWNER);
                $aclProvider->updateAcl($acl);
            }

            if ($request->isXmlHttpRequest())
            {
                $result = array();
                $result['event'] = 'list_created';
                $result['object'] = $templating->render('WhootBundle:LList:teaser.html.twig', array('list' => $list));
                $result['flash'] = array('type' => 'success', 'message' => $flashMessage);
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            $this->container->get('session')->setFlash('notice', $flashMessage);

            return new RedirectResponse($redirectUrl);
        }

        if ($chromeless || $request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = $request->getMethod() == 'POST' ? 'error' : 'success';
            $result['event'] = 'list_form';
            $result['form'] = $templating->render('WhootBundle:LList:new_content.html.twig', array('form' => $form->createView()));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $templating->renderResponse('WhootBundle:LList:new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function editAction($id, $_format)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin('xml');
        if ($login)
        {
            return $login;
        }

        $object = $this->container->get('whoot.manager.list')->findObjectBy(array('id' => $id), true);
        $denied = $coreManager->accessDenied('EDIT', $object, 'error', 'You do not have permission to edit this list!');
        if ($denied)
        {
            return $denied;
        }

        $form = $this->container->get('whoot.form.list');
        $form->process($object);

        return $this->container->get('templating')->renderResponse('WhootBundle:LList:edit.html.twig', array(
            'form'      => $form,
            'object'    => $object,
            '_format'   => $_format
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function updateAction($id)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $object = $this->container->get('whoot.manager.list')->findObjectBy(array('id' => $id), true);
        $form = $this->container->get('whoot.form.list');

        $process = $form->process($object);
        if ($process) {
            $this->container->get('session')->setFlash('notice', 'List updated successfully!');
            $listUrl =  $this->container->get('router')->generate('list_show', array('id' => $object->getId(), 'slug' => $object->getSlug()));
            return new RedirectResponse($listUrl);
        }

        $request = $this->container->get('request');
        $templating = $this->container->get('templating');
        $title = 'Edit List';

        if ($request->isXmlHttpRequest())
        {
            $result = array();
            $result['result'] = 'error';
            $result['form'] = $templating->render('WhootBundle:LList:edit_content.html.twig', array('form' => $form, 'object' => $object));
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return $templating->renderResponse('WhootBundle:LList:edit.html.twig', array(
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
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $listManager = $this->container->get('whoot.manager.list');
        $list = $listManager->findListBy(array('id' => $id), true);
        $denied = $coreManager->accessDenied('EDIT', $list, 'error', 'You do not have permission to delete this list!');
        if ($denied)
        {
            return $denied;
        }

        $request = $this->container->get('request');
        $result = $listManager->deleteList($list);

        if ($request->isXmlHttpRequest())
        {
            $result['event'] = 'list_deleted';
            $result['objectId'] = $id;
            $result['flash'] = array('type' => 'success', 'message' => 'List successfully deleted!');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        
        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    /**
     * {@inheritDoc}
     */
    public function showAction($id)
    {
        $request = $this->container->get('request');
        $response = new Response();
        $response->setCache(array(
        ));

        if ($response->isNotModified($request)) {
            // return the 304 Response immediately
            // return $response;
        }

        $list = $this->container->get('whoot.manager.list')->findListUsers($id);

        return $this->container->get('templating')->renderResponse('WhootBundle:LList:show.html.twig', array(
            'list' => $list
        ), $response);
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

        $object = $this->container->get('whoot.manager.list')->findObjectBy(array('id' => $id));

        return $this->container->get('templating')->renderResponse('WhootBundle:LList:teaser.html.twig', array(
            'object' => $object
        ), $response);
    }

    /*
     * Adds a user to a list
     *
     * @param integer $listId
     */
    public function userAddAction($listId)
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $userId = $request->request->get('userId', null);
        $feedReload = $request->request->get('feedReload', true);

        if (!$userId)
        {
            $result = array();
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'Oops, there was an error! [C: LUA01]');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $userList = $this->container->get('whoot.manager.list')->findUserList(array('list' => $listId, 'user' => $userId), true);
        if ($userList && $userList->getStatus() == 'Active')
        {
            $result = array();
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'You already added this user to this list.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $list = $this->container->get('whoot.manager.list')->findListBy(array('id' => $listId), true);
//        $denied = $coreManager->accessDenied('EDIT', $list, 'error', 'You do not have permission to edit this list!');
//        if ($denied)
//        {
//            return $denied;
//        }

        $user = $this->container->get('whoot.manager.list')->addUser($userList, $list, $userId);

        if ($user && $request->isXmlHttpRequest())
        {
            $result = array();
            $result['event'] = 'list_user_added';
            if ($feedReload != 'no')
            {
                $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array('listId' => $listId));
                $result['feed'] = $feed->getContent();
            }
            $result['user'] = $this->container->get('templating')->render('WhootUserBundle:Profile:tag.html.twig', array('user' => $user));
            $result['flash'] = array('type' => 'success', 'message' => 'User added to list successfully!');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
    
    /*
     * Deletes a user from a list.
     *
     * @param integer $userListId
     */
    public function userDeleteAction()
    {
        $coreManager = $this->container->get('whoot.manager.core');
        $login = $coreManager->mustLogin();
        if ($login)
        {
            return $login;
        }

        $request = $this->container->get('request');
        $userListId = $request->query->get('userListId', null);
        $listId = $request->query->get('listId', null);

        if (!$userListId || !$listId)
        {
            $result = array();
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'Oops, there was an error! [C: LUD01]');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $userList = $this->container->get('whoot.manager.list')->findUserList(array('id' => $userListId), true);
        if (!$userList)
        {
            $result = array();
            $result['result'] = 'error';
            $result['flash'] = array('type' => 'error', 'message' => 'Hmm... This user doesn\'t seem to belong to this list!');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

//        $list = $this->container->get('whoot.manager.list')->findListBy(array('id' => $listId), true);
//        $denied = $coreManager->accessDenied('EDIT', $list, 'error', 'You do not have permission to edit this list!');
//        if ($denied)
//        {
//            return $denied;
//        }

        $user = $this->container->get('whoot.manager.list')->deleteUserList($userList);

        if ($user && $request->isXmlHttpRequest())
        {
            $result = array();
            $result['event'] = 'list_user_deleted';
            $feed = $this->container->get('http_kernel')->forward('WhootBundle:Post:feed', array('listId' => $listId));
            $result['feed'] = $feed->getContent();
            $result['flash'] = array('type' => 'success', 'message' => 'User removed successfully!');
            $result['objectId'] = $userListId;
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}