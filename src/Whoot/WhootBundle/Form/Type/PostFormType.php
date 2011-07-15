<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class PostFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('type', 'hidden')
            ->add('words', 'collection', array('type' => new WordFormType()));
    }

    public function getName()
    {
        return 'whoot_post_form';
    }
}