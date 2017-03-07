<?php

namespace Geoks\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class GenerateAppCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('generate:geoks:app')
            ->setDescription('generate the app for geoks interface')
            ->addArgument('project-name', InputArgument::REQUIRED, 'Set the project name')
            ->addOption('website');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appName = $input->getArgument('project-name');
        $kernel = $this->getContainer()->get('kernel');
        $fs = new Filesystem();

        // Generate bundles
        if (!$fs->exists($kernel->getRootDir() . '/../src/AppBundle')) {
            $bundles[] = exec('php app/console generate:bundle --format=annotation --namespace=AppBundle --bundle-name=AppBundle --no-interaction');
        }

        if (!$fs->exists($kernel->getRootDir() . '/../src/AdminBundle')) {
            $bundles[] = exec('php app/console generate:bundle --bundle-name=AdminBundle --format=annotation --namespace=AdminBundle --no-interaction');
        }

        if ($input->getOption('website') && !$fs->exists($kernel->getRootDir() . '/../src/WebBundle')) {
            $bundles[] = exec('php app/console generate:bundle --bundle-name=WebBundle --format=annotation --namespace=WebBundle --no-interaction');
        }

        if (!$fs->exists($kernel->getRootDir() . '/../composer.json')) {
            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/composer.json.dist',
                $kernel->getRootDir() . '/../composer.json'
            );
        }

        exec('sudo composer self-update');
        exec('sudo composer update');

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/config.yml.dist',
            $kernel->getRootDir() . '/config/config.yml',
            true
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AppKernel.php.dist',
            $kernel->getRootDir() . '/AppKernel.php',
            true
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/parameters.yml.dist',
            $kernel->getRootDir() . '/config/parameters.yml.dist',
            true
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/parameters.yml.dist',
            $kernel->getRootDir() . '/config/parameters.yml',
            true
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/security.yml.dist',
            $kernel->getRootDir() . '/config/security.yml',
            true
        );

        // Parameters dist
        $content = file_get_contents($kernel->getRootDir() . '/config/parameters.yml.dist');
        $content = str_replace("projectName", $appName, $content);

        file_put_contents($kernel->getRootDir() . '/config/parameters.yml.dist', $content);

        // Parameters
        $content = file_get_contents($kernel->getRootDir() . '/config/parameters.yml');
        $content = str_replace("projectName", $appName, $content);

        file_put_contents($kernel->getRootDir() . '/config/parameters.yml', $content);

        // Config
        $content = file_get_contents($kernel->getRootDir() . '/config/config.yml');
        $content = str_replace("projectName", $appName, $content);

        file_put_contents($kernel->getRootDir() . '/config/config.yml', $content);

        // Security
        $content = file_get_contents($kernel->getRootDir() . '/config/security.yml');
        $content = str_replace("projectName", $appName, $content);

        file_put_contents($kernel->getRootDir() . '/config/security.yml', $content);

        // Create User Entity
        if (!$fs->exists($kernel->getRootDir() . '/../src/AppBundle/Entity/User.php')) {
            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/UserBundle/Entity/User.php.dist',
                $kernel->getRootDir() . '/../src/AppBundle/Entity/User.php'
            );
        }

        // Create Database and schema
        exec('php app/console doctrine:database:create');
        exec('php app/console doctrine:schema:create');

        // Npm and Bower
        exec('npm install');
        exec('bower install');

        // Remove Default Elements
        $fs->remove($kernel->getRootDir() . '/../tests');

        $fs->remove($kernel->getRootDir() . '/../AdminBundle/Controller/DefaultController.php');
        $fs->remove($kernel->getRootDir() . '/../AdminBundle/Resources/views/Default');

        $fs->remove($kernel->getRootDir() . '/../AppBundle/Controller/DefaultController.php');
        $fs->remove($kernel->getRootDir() . '/../AppBundle/Resources/views/Default');
    }
}
