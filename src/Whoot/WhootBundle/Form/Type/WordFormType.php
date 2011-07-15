<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class WordFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('content', 'text');
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Whoot\WhootBundle\Entity\Word',
        );
    }

    public function getName()
    {
        return 'whoot_word_form';
    }
}