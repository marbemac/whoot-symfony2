<?php

namespace Whoot\WhootBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('content', 'textarea')
            ->add('post', 'hidden')
            ->add('invite', 'hidden');
    }

    public function getName()
    {
        return 'whoot_comment_form';
    }
}