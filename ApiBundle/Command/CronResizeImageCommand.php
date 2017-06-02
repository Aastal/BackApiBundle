<?php

namespace Geoks\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronResizeImageCommand extends CronTaskCommand
{
    protected $name = "resizeImage";
    protected $description = "Resize images in amazone s3 server.";

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');


    }
}
