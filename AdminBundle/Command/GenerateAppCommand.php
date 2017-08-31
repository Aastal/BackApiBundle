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
        $bundles[] = exec('php bin/console generate:bundle --format=annotation --namespace=AppBundle --bundle-name=AppBundle --no-interaction');

        $bundles[] = exec('php bin/console generate:bundle --bundle-name=AdminBundle --format=annotation --namespace=AdminBundle --no-interaction');

        if ($input->getOption('website')) {
            $bundles[] = exec('php app/console generate:bundle --bundle-name=WebBundle --format=annotation --namespace=WebBundle --no-interaction');
        }

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/composer.json.dist',
            $kernel->getRootDir() . '/../composer.json'
        );

        exec('php bin/console doctrine:database:create');
        exec('php bin/console doctrine:schema:create');

        exec('sudo composer self-update');
        exec('sudo composer update');

        exec('sudo rm -Rf app/cache/*');

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
            $kernel->getRootDir() . '/../src/Geoks/routing.yml.dist',
            $kernel->getRootDir() . '/config/routing.yml',
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
        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/UserBundle/Entity/User.php.dist',
            $kernel->getRootDir() . '/../src/AppBundle/Entity/User.php'
        );

        // Update schema
        exec('php bin/console doctrine:schema:update --force');

        // Npm and Bower
        exec('cd src/Geoks && npm install');
        exec('cd src/Geoks && bower install');

        // Remove Default Elements
        $fs->remove($kernel->getRootDir() . '/../tests');

        $fs->remove($kernel->getRootDir() . '/../src/AdminBundle/Controller/DefaultController.php');
        $fs->remove($kernel->getRootDir() . '/../src/AdminBundle/Resources/views/Default');

        $fs->remove($kernel->getRootDir() . '/../src/AppBundle/Controller/DefaultController.php');
        $fs->remove($kernel->getRootDir() . '/../src/AppBundle/Resources/views/Default');
    }
}
