<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class InviteFormType extends AbstractType
{
    public function __construct($formFactory) {
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('type', 'hidden')
            ->add('description', 'textarea')
            ->add('venue')
            ->add('address')
            ->add('coordinates', 'hidden', array('required' => false))
            ->add('time')
            ->add('image', 'file', array('required' => false))
            ->add('currentLocation', 'choice');
    }

    public function getName()
    {
        return 'whoot_invite_form';
    }
}