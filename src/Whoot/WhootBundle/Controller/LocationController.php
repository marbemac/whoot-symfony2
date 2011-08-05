<?php

namespace Whoot\WhootBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LocationController extends ContainerAware
{
    /**
     * Creates a new location.
     */
    public function createAction()
    {
        $templating = $this->container->get('templating');
        $request = $this->container->get('request');
        $form = $this->container->get('whoot.form.location');
        $formHandler = $this->container->get('whoot.form.handler.location');

        if ($formHandler->process(null) === true) {
            $post = $form->getData();
            $this->container->get('session')->setFlash('notice', 'Location added!');

            return new RedirectResponse($_SERVER['HTTP_REFERER']);
        }

        return $templating->renderResponse('WhootBundle:Location:new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function deleteCityAction($cityId)
    {
        $this->container->get('whoot.manager.location')->deleteCity($cityId);
        $this->container->get('session')->setFlash('notice', 'City deleted!');

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    public function deleteSchoolAction($schoolId)
    {
        $this->container->get('whoot.manager.location')->deleteSchool($schoolId);
        $this->container->get('session')->setFlash('notice', 'School deleted!');

        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }

    public function createSchoolAction()
    {
        $cityId = $this->container->get('request')->request->get('cityId', null);
        $school = $this->container->get('request')->request->get('school', null);

        $this->container->get('whoot.manager.location')->addSchool($cityId, $school);
        $this->container->get('session')->setFlash('notice', 'School added!');
        
        return new RedirectResponse($_SERVER['HTTP_REFERER']);
    }
}