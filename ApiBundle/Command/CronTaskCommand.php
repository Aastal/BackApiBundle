<?php

namespace Geoks\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CronTaskCommand extends ContainerAwareCommand
{
    protected $name = null;
    protected $description = null;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        if (!$this->name) {
            return new \RuntimeException('Define a name property');
        }

        if (!$this->description) {
            return new \RuntimeException('Define a description property');
        }

        $this
            ->setName(sprintf('cron:task:%s', $this->name))
            ->setDescription($this->description);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
