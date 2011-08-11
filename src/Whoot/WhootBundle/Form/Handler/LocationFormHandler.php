<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Document\Location;
use Whoot\WhootBundle\Document\LocationManager;

class LocationFormHandler
{
    protected $form;
    protected $request;
    protected $locationManager;

    public function __construct(Form $form, Request $request, LocationManager $locationManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->locationManager = $locationManager;
    }

    public function process(Location $location = null)
    {
        if (null === $location) {
            $location = $this->locationManager->createLocation();
        }

        $this->form->setData($location);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid())
            {
                $location->setCities(array());
                $params = $this->request->request->all();
                $params = $params['whoot_location_form'];

                $existing = $this->locationManager->findLocationBy(array('state' => $params['state']));
                $location = $existing ? $existing : $location;

                $location->addCity($params['cities']);
                $location->setCityTimezone($params['cities'], $params['timezone']);

                $this->locationManager->updateLocation($location);

                return true;
            }

            return false;
        }
    }
}