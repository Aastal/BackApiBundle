<?php

namespace Geoks\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
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
class GenerateTranslationCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('generate:entity:translations')
            ->setDescription('generate translation based on entity fields')
            ->addArgument('class', InputArgument::REQUIRED, 'Sets the entity class', null);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = new Yaml();
        $kernel = $this->getContainer()->get('kernel');

        $entityNamespace = $this->getContainer()->getParameter('geoks_api.app_bundle');
        $entityFields = $this->getContainer()->get('geoks_admin.entity_fields')->getFieldsName($input->getArgument('class'));
        $entityName = strtolower($this->getContainer()->get('doctrine')->getManager()->getClassMetadata($input->getArgument('class'))->getReflectionClass()->getShortName());

        $array = [];
        foreach ($entityFields as $name => $type) {
            $array[$entityName][$name] = $name;
        }

        $yaml = $parser->dump($array);

        $file = $kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources/translations/' . $entityName . '.fr.yml';

        if (!file_exists($file)) {
            if (!file_exists($kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources/translations')) {
                mkdir($kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources/translations');
            }

            touch($file);
        }

        file_put_contents($file, $yaml);
    }
}
