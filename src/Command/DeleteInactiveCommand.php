<?php

namespace App\Command;

use DateInterval;
use DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DeleteInactiveCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('dtdb:inactive-users')
        ->setDescription('Delete users inactive since 48 hours')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $limit = new DateTime();
        $limit->sub(new DateInterval('PT48H'));
        $count = 0;

        $users = $em->getRepository('DtdbUserBundle:User')->findBy(array('enabled' => false));
        foreach($users as $user) {
            /* @var $user App\Entity\User */
            if($user->getCreation() < $limit) {
                $count++;
                $em->remove($user);
            }
        }
        $em->flush();
        $output->writeln(date('c') . " Delete $count inactive users.");
        return 0;
    }
}
