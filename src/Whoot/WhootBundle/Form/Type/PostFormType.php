<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Whoot\UserBundle\Entity\UserManager;

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
        $userLocation = $this->userManager->getUserLocation($this->securityContext->getToken()->getUser()->getId());
        $builder
            ->add('type', 'hidden')
            ->add('words', 'collection', array('type' => new WordFormType()))
            ->add('location', 'entity', array(
                'class' => 'Whoot\WhootBundle\Entity\Location',
                'preferred_choices' => array($userLocation ? $userLocation['id'] : '')
            ));
    }

    public function getName()
    {
        return 'whoot_post_form';
    }
}