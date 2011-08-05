<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Whoot\UserBundle\Document\UserManager;

class PostFormType extends AbstractType
{
    private $userManager;
    private $securityContext;

    public function __construct($formFactory, $userManager, $securityContext) {
        $this->userManager = $userManager;
        $this->securityContext = $securityContext;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
//        $userLocation = $this->userManager->getUserLocation($this->securityContext->getToken()->getUser()->getId());
        $builder
            ->add('type', 'hidden')
            ->add('tags', 'collection', array('type' => new TagFormType()))
            ->add('currentLocation', 'choice');
    }

    public function getName()
    {
        return 'whoot_post_form';
    }
}