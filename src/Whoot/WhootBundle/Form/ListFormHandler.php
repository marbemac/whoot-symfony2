<?php

namespace Whoot\WhootBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\Error;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Entity\LList;
use Whoot\WhootBundle\Entity\LlistManager;

class ListFormHandler
{
    protected $form;
    protected $request;
    protected $listManager;

    public function __construct(Form $form, Request $request, LlistManager $listManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->listManager = $listManager;
    }
    
    public function process(LList $list = null)
    {
        if (null === $list) {
            $list = $this->listManager->createLList();
        }

        $this->form->setData($list);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {
                $list->setSlug($list->getName());
                $this->listManager->updateList($list);

                return true;
            }
        }

        return false;
    }
}