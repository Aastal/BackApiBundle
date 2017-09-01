<?php

namespace Geoks\AdminBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanPasswordCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('geoks:clean-passwords')
            ->setDescription("Clean a user password")
            ->addArgument('id', InputArgument::REQUIRED, 'The id of the user.')
        ;

    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $user = $em->getRepository('AppBundle:Client')->find($input->getArgument('id'));

        $encoder = $this->getContainer()->get('security.password_encoder');
        $encoded = $encoder->encodePassword($user, $user->getPassword());

        $user->setPassword($encoded);

        $em->persist($user);
        $em->flush();
    }
}
