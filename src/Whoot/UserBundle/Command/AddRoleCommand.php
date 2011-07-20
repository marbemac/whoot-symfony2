<?php

namespace Whoot\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use FOS\UserBundle\Model\User;

class AddRoleCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('whoot:user:addrole')
            ->setDescription('Adds a role to a user')
            ->setDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'The user\'s email'),
                new InputArgument('role', InputArgument::REQUIRED, 'The role to add'),
            ))
            ->setHelp(<<<EOT
The <info>whoot:user:addrole</info> command adds a role to a user

  <info>php app/console whoot:user:addrole foo@bar.com ROLE_FOO</info>
EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        $userManager = $this->getContainer()->get('whoot.user_manager');
        $user = $userManager->findUserBy(array('email' => $email));

        if ($user)
        {
            $user->addRole($role);
            $userManager->updateUser($user);
        }

        $output->writeln(sprintf('Role "%s" has been added to user with email "%s".', $role, $email));
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('email')) {
            $email = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an email:',
                function($email)
                {
                    if (empty($email)) {
                        throw new \Exception('Email can not be empty');
                    }
                    return $email;
                }
            );
            $input->setArgument('email', $email);
        }

        if (!$input->getArgument('role')) {
            $role = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a role:',
                function($role)
                {
                    if (empty($role)) {
                        throw new \Exception('Role can not be empty');
                    }
                    return $role;
                }
            );
            $input->setArgument('role', $role);
        }
    }
}
