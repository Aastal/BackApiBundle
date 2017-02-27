<?php

namespace Geoks\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use BCC\CronManagerBundle\Manager\CronManager;
use BCC\CronManagerBundle\Manager\Cron;

/**
 * Class CronBuilderCommand
 * Used to setup commands as crons, read configuration into the file crontab.yml and add cronjob accordingly
 */
class CronBuilderCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('cron:builder')
            ->setDescription('Build cron table');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Parsing the crontab.yml file to set cronjobs on the server
        $parser = new Yaml();

        $cronManager = new CronManager();
        $kernel = $this->getContainer()->get('kernel');
        $entries = $parser->parse($kernel->getRootDir() . '/config/crontab.yml');

        $appName = explode('/', $kernel->getRootDir());
        $appName = $appName[count($appName) -2];

        $env = $this->getContainer()->getParameter('app_env');

        $crons = $cronManager->get();

        foreach ($crons as $key => $entry) {
            if ($entry->getComment() == $appName) {
                $cronManager->remove($key);
            }
        }

        // For each cronjobs configured in file
        foreach ($entries as $key => $entry) {
            // All fields enumarate below are mandatory
            foreach (array('minute', 'hour', 'month', 'day_of_month', 'day_of_week') as $item) {
                if (!isset($entry[$item])) {
                    throw new \InvalidArgumentException(sprintf("%s doesn't exists for cron %s", $item, $key));
                }
            }

            // Check if associate command launch by the cron i available
            if (!isset($entry['task']) && !isset($entry['command']) && !isset($entry['console'])) {
                throw new \InvalidArgumentException(sprintf("You need to define a task or command entry for cron %s", $key));
            }

            $command = null;

            // The is a difference between task and console types. A task is a already formatted command and a console command setup a cron for utilities cronjobs Ex : currency update
            if (isset($entry['task'])) {
                $command = sprintf(
                    '%s %s/console cron:task:%s',
                    $this->getContainer()->getParameter('backend_process_php_binary_path'),
                    $kernel->getRootDir(),
                    $entry['task']
                );
            } elseif(isset($entry['console'])) {
                $command = sprintf(
                    '%s %s/console %s',
                    $this->getContainer()->getParameter('backend_process_php_binary_path'),
                    $kernel->getRootDir(),
                    $entry['console']
                );
            } else {
                $command = $entry['command'];
            }

            //Behavior may be different between prod and dev mode (cache or asset loading for example)
            $command .= " --env=" . $env;

            // We use the Cron object mapping system available on BCCronManagerBundle
            $cron = new Cron();
            $cron->setCommand($command);
            $cron->setMinute($entry['minute']);
            $cron->setHour($entry['hour']);
            $cron->setMonth($entry['month']);
            $cron->setDayOfMonth($entry['day_of_month']);
            $cron->setDayOfWeek($entry['day_of_week']);
            // We set here the AppName because the server could have multiple instance of projects but just on crontab by user.
            //This prevent this projet erase other projets cronjobs
            $cron->setComment($appName);

            $cronManager->add($cron);
        }

        $cronManager->write();

        if ($cronManager->getError()) {
            $output->writeln(sprintf('<error>%s</error>', $cronManager->getError()));
        } else {
            $output->writeln('<info>Everything went fine : crontab is installed</info>');
        }
    }
}
