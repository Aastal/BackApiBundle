<?php

namespace Geoks\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

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
            ->addArgument('class', InputArgument::OPTIONAL, 'Sets the entity class', 'AppBundle:User');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = new Yaml();
        $kernel = $this->getContainer()->get('kernel');

        $entityNamespace = $this->getContainer()->getParameter('geoks_api.app_bundle');
        $entityName = strtolower($this->getContainer()->get('doctrine')->getManager()->getClassMetadata($input->getArgument('class'))->getReflectionClass()->getShortName());

        $entityFields = $this->getContainer()->get('geoks_admin.entity_fields')->getFieldsName($input->getArgument('class'));
        $entityFields["id"] = "ID";

        $array = [];
        foreach ($entityFields as $name => $type) {
            $array[$entityName][$name] = $name;
        }

        $yaml = $parser->dump($array);

        $file = $kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources/translations/' . $entityName . '.fr.yml';

        if (!file_exists($file)) {
            if (!file_exists($kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources')) {
                mkdir($kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources');

                if (!file_exists($kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources/translations')) {
                    mkdir($kernel->getRootDir() . '/../src/' . $entityNamespace . '/Resources/translations');
                }
            }

            touch($file);
        }

        file_put_contents($file, $yaml);
    }
}
