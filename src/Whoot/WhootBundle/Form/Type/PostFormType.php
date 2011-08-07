<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class PostFormType extends AbstractType
{
    public function __construct($formFactory) {
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
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