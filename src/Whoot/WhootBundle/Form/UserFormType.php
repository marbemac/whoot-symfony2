<?php

namespace Whoot\WhootBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use FOS\UserBundle\Form\UserFormType as BaseForm;

class UserFormType extends BaseForm
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('gender', 'choice', array(
                'choices' => array(
                    'm1' => 'Male', 'm2' => 'Guy', 'm3' => 'Dude',
                    'f1' => 'Female', 'f2' => 'Girl', 'f3' => 'Chick'
                )
            ))
            ->add('zipcode')
            ->add('plainPassword', 'repeated', array('type' => 'password'))
            ->add('username', 'hidden');
    }
}