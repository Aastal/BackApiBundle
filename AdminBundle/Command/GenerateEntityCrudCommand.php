<?php

namespace Geoks\AdminBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class GenerateEntityCrudCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('generate:geoks:crud')
            ->setDescription('generate entities CRUD')
            ->addArgument('class', InputArgument::OPTIONAL, 'Generate the class CRUD');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getContainer()->get('kernel');
        $fs = new Filesystem();

        $em = $this->getContainer()->get('doctrine')->getManager();

        // Admin Default views
        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/Security/login.html.twig',
            $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/Security/login.html.twig'
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/layout.html.twig',
            $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/layout.html.twig'
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/index.html.twig',
            $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/index.html.twig'
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/simple.html.twig',
            $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/simple.html.twig'
        );

        // Admin Default Controller and ApiDoc
        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/AdminDoc.php.dist',
            $kernel->getRootDir() . '/../src/AdminBundle/Controller/ApiDocs/AdminDoc.php'
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/AdminController.php.dist',
            $kernel->getRootDir() . '/../src/AdminBundle/Controller/AdminController.php'
        );

        // App Security
        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/ApiBundle/Templates/SecurityDoc.php.dist',
            $kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/SecurityDoc.php'
        );

        $fs->copy(
            $kernel->getRootDir() . '/../src/Geoks/ApiBundle/Templates/SecurityController.php.dist',
            $kernel->getRootDir() . '/../src/AppBundle/Controller/SecurityController.php'
        );

        if ($class = $input->getArgument('class')) {
            $meta = $em->getMetadataFactory()->getMetadataFor($class);

            $this->generateByMeta($meta);
        } else {
            $meta = $em->getMetadataFactory()->getAllMetadata();

            // Foreach entity create the geoks crud
            foreach ($meta as $m) {
                $this->generateByMeta($m);
            }
        }
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $m
     */
    private function generateByMeta($m)
    {
        $kernel = $this->getContainer()->get('kernel');
        $fs = new Filesystem();

        $name = $m->getReflectionClass()->getShortName();

        // Check if the entity is in the project
        if (strpos($m->getName(), "AppBundle") !== false) {

            // Admin Doc Part
            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/TemplateDoc.php.dist',
                $kernel->getRootDir() . '/../src/AdminBundle/Controller/ApiDocs/' . $name . 'Doc.php'
            );

            $content = file_get_contents($kernel->getRootDir() . '/../src/AdminBundle/Controller/ApiDocs/' . $name . 'Doc.php');
            $content = str_replace("Template", $name, $content);

            $namePluralize = $this->getContainer()->get('geoks.utils.string_manager')->pluralize($name);

            $content = str_replace("template", lcfirst($namePluralize), $content);

            file_put_contents($kernel->getRootDir() . '/../src/AdminBundle/Controller/ApiDocs/' . $name . 'Doc.php', $content);

            // Admin Controller Part
            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/TemplateController.php.dist',
                $kernel->getRootDir() . '/../src/AdminBundle/Controller/' . $name . 'Controller.php'
            );

            $content = file_get_contents($kernel->getRootDir() . '/../src/AdminBundle/Controller/' . $name . 'Controller.php');
            $content = str_replace("Template", $name, $content);

            file_put_contents($kernel->getRootDir() . '/../src/AdminBundle/Controller/' . $name . 'Controller.php', $content);

            // Admin Resources Part
            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/Template/form.html.twig',
                $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/' . $name . '/form.html.twig'
            );

            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/Template/index.html.twig',
                $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/' . $name . '/index.html.twig'
            );

            $fs->copy(
                $kernel->getRootDir() . '/../src/Geoks/AdminBundle/Templates/views/Template/show.html.twig',
                $kernel->getRootDir() . '/../src/AdminBundle/Resources/views/' . $name . '/show.html.twig'
            );

            // Check if class User or not and do so
            if ($m->getReflectionClass()->getShortName() != 'User') {

                // App Part ApiDocs
                $fs->copy(
                    $kernel->getRootDir() . '/../src/Geoks/ApiBundle/Templates/TemplateDoc.php.dist',
                    $kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/' . $name . 'Doc.php'
                );

                $content = file_get_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/' . $name . 'Doc.php');
                $namePluralize = $this->getContainer()->get('geoks.utils.string_manager')->pluralize($name);

                $content = str_replace("TemplateSection", $namePluralize, $content);
                $content = str_replace("Template", $name, $content);
                $content = str_replace("template", lcfirst($namePluralize), $content);

                file_put_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/' . $name . 'Doc.php', $content);

                // App Controller Part
                $fs->copy(
                    $kernel->getRootDir() . '/../src/Geoks/ApiBundle/Templates/TemplateController.php.dist',
                    $kernel->getRootDir() . '/../src/AppBundle/Controller/' . $name . 'Controller.php'
                );

                $content = file_get_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/' . $name . 'Controller.php');
                $content = str_replace("Template", $name, $content);

                file_put_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/' . $name . 'Controller.php', $content);
            } else {

                // App Part ApiDocs
                $fs->copy(
                    $kernel->getRootDir() . '/../src/Geoks/ApiBundle/Templates/UserDoc.php.dist',
                    $kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/' . $name . 'Doc.php'
                );

                $content = file_get_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/' . $name . 'Doc.php');

                $content = str_replace("Template", $name, $content);
                $content = str_replace("template", lcfirst($namePluralize), $content);

                file_put_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/ApiDocs/' . $name . 'Doc.php', $content);

                // App Controller Part
                $fs->copy(
                    $kernel->getRootDir() . '/../src/Geoks/ApiBundle/Templates/UserController.php.dist',
                    $kernel->getRootDir() . '/../src/AppBundle/Controller/' . $name . 'Controller.php'
                );

                $content = file_get_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/' . $name . 'Controller.php');
                $content = str_replace("Template", $name, $content);

                file_put_contents($kernel->getRootDir() . '/../src/AppBundle/Controller/' . $name . 'Controller.php', $content);
            }

            exec("php bin/console generate:doctrine:entities AppBundle:" . $m->getReflectionClass()->getShortName());
        }
    }
}
