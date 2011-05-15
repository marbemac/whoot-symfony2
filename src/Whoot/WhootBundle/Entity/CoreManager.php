<?php

namespace Whoot\WhootBundle\Entity;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CoreManager
{
    protected $request;
    protected $securityContext;

    /**
     * Constructor.
     *
     * @param Request $request
     * @param SecurityContext $securityContext
     */
    public function __construct(Request $request, Router $router, SecurityContext $securityContext)
    {
        $this->request = $request;
        $this->router = $router;
        $this->securityContext = $securityContext;
    }

    /**
     * Checks if the current user is logged in.
     * Returns a response if user needs to login, else returns false.
     *
     * @return response|false
     */
    public function mustLogin($_format='json')
    {
        if (!$this->securityContext->isGranted('ROLE_USER'))
        {
            if ($this->request->isXmlHttpRequest())
            {
                if ($_format == 'xml')
                {
                    $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>
                        <application>
                            <result>login</result>
                        </application>
                    ';

                    $response = new Response($xml);
                    $response->headers->set('Content-Type', 'application/xml');
                }
                else
                {
                    $response = new Response(json_encode(array('result' => 'login')));
                    $response->headers->set('Content-Type', 'application/json');
                }
            }
            else
            {
                $response = new RedirectResponse($this->router->generate('_security_login'));
            }
            return $response;
        }
        else
        {
            return false;
        }
    }

    public function accessDenied($permission, $object, $flashType=null, $flashMessage=null)
    {
        if (false === $this->securityContext->isGranted($permission, $object))
        {
            if ($this->request->isXmlHttpRequest())
            {
                $result = array();
                $result['result'] = 'error';
                $result['flash'] = array('type' => $flashType, 'message' => $flashMessage);
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }

            throw new AccessDeniedException();
        }

        return false;
    }
}
