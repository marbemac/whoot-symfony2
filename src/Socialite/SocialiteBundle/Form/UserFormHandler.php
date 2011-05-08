<?php

namespace Socialite\SocialiteBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\TextField;
use Symfony\Component\Form\RepeatedField;
use Symfony\Component\Form\PasswordField;

use Symfony\Component\Validator\ValidatorInterface;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Form\UserFormHandler as BaseUserForm;

class UserFormHandler extends BaseUserForm
{
    public function process(UserInterface $user = null, $confirmation = null)
    {
        if (null === $user) {
            $user = $this->userManager->createUser();
        }

        $this->setData($user);

        if ('POST' == $this->request->getMethod()) {
            $this->bind($this->request);

            if ($this->isValid()) {
                if (true === $confirmation) {
                    $user->setEnabled(false);
                } else if (false === $confirmation) {
                    $user->setConfirmationToken(null);
                    $user->setEnabled(true);
                }

                $this->userManager->updateUser($user);

                return true;
            }
        }

        return false;
    }
}