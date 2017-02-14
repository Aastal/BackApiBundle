<?php

namespace Geoks\AdminBundle\Twig;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            'class' => new \Twig_SimpleFunction('class', array($this, 'getClass')),
            'name_pluralize' => new \Twig_SimpleFunction('name_pluralize', array($this, 'getNamePluralize')),
            'class_repository' => new \Twig_SimpleFunction('class_repository', array($this, 'getRepository')),
            'fields' => new \Twig_SimpleFunction('fields', array($this, 'getFieldsName')),
            'fields_associations' => new \Twig_SimpleFunction('fields_associations', array($this, 'getFieldsAssociations'))
        );
    }

    public function getName()
    {
        return 'geoks_admin_extension';
    }

    public function getClass($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    public function getNamePluralize($object)
    {
        $object = $this->container->get('geoks.api.pluralization')->pluralize($object);

        return $object;
    }

    public function getRepository($object)
    {
        return (new \ReflectionClass($object))->getNamespaceName();
    }

    public function getFieldsName($table)
    {
        return $this->container->get('geoks_admin.entity_fields')->getFieldsName($table);
    }

    public function getFieldsAssociations($table)
    {
        return $this->container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);
    }
}
