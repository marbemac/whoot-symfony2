<?php

namespace Whoot\WhootUserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use FOS\UserBundle\Form\RegistrationFormType as BaseForm;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('gender', 'choice', array(
                'choices' => array(
                    'm' => 'Male',
                    'f' => 'Female'
                )
            ))
            ->add('plainPassword', 'repeated', array('type' => 'password'))
            ->add('username', 'hidden');
    }

    public function getName()
    {
        return 'whoot_user_registration';
    }
}