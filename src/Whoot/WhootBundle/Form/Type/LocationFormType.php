<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('state', 'state')
            ->add('cities')
            ->add('timezone', 'timezone');
    }

    public function getName()
    {
        return 'whoot_location_form';
    }
}