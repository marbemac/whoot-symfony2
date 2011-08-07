<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\Error;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Document\LList;
use Whoot\WhootBundle\Document\LlistManager;

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
    
    public function process(LList $list = null, $createdBy)
    {
        if (null === $list) {
            $list = $this->listManager->createLList();
        }

        $this->form->setData($list);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {
                $list->setCreatedBy($createdBy);
                $this->listManager->updateLList($list);

                return true;
            }
        }

        return false;
    }
}