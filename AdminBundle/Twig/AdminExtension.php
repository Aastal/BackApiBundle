<?php

namespace Geoks\AdminBundle\Twig;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Column;
use Geoks\AdminBundle\Services\EntityFields;
use Geoks\ApiBundle\Utils\StringUtils;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class AdminExtension extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var StringUtils
     */
    private $stringManager;

    /**
     * @var EntityFields
     */
    private $entityFields;

    public function __construct(EntityManager $em, Router $router, $stringManager, $entityFields)
    {
        $this->em = $em;
        $this->router = $router;
        $this->stringManager = $stringManager;
        $this->entityFields = $entityFields;
    }

    public function getFunctions()
    {
        return array(
            'class' => new \Twig_SimpleFunction('class', array($this, 'getClass')),
            'property_type' => new \Twig_SimpleFunction('property_type', array($this, 'getPropertyType')),
            'routeExists' => new \Twig_SimpleFunction('route_exists', array($this, 'routeExists')),
            'jms_groups_properties' => new \Twig_SimpleFunction('jms_groups_properties', array($this, 'getJmsGroupsProperties')),
            'get_entity' => new \Twig_SimpleFunction('get_entity', array($this, 'getEntity')),
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

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    public function getJmsGroupsProperties($reflection)
    {
        $groups = [];
        $reader = new AnnotationReader();

        foreach ($reflection->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation($property, "JMS\\Serializer\\Annotation\\Groups")) {

                foreach ($annotation->groups as $group) {
                    $groups[$group][] = $this->stringManager->fromCamelCase($property->name);
                }
            }
        }

        $associations = $this->entityFields->getFieldsAssociations($reflection->name);

        foreach ($associations as $association) {
            $association = new \ReflectionProperty($reflection->name, $association["fieldName"]);

            if ($annotation = $reader->getPropertyAnnotation($association, "JMS\\Serializer\\Annotation\\Groups")) {
                foreach ($annotation->groups as $group) {
                    $groups[$group][] = $this->stringManager->fromCamelCase($association->name);
                }
            }
        }

        return $groups;
    }

    /**
     * @param \ReflectionProperty $object
     * @return null|array
     */
    public function getPropertyType($object)
    {
        $reader = new AnnotationReader();

        $annotation = $reader->getPropertyAnnotations($object);

        return $annotation;
    }

    public function getEntity($repository, $id)
    {
        return $this->em->getRepository($repository)->find($id);
    }

    public function getNamePluralize($object)
    {
        $object = $this->stringManager->pluralize($object);

        return $object;
    }

    public function getRepository($object)
    {
        return (new \ReflectionClass($object))->getNamespaceName();
    }

    public function getFieldsName($table)
    {
        return $this->entityFields->getFieldsName($table);
    }

    public function getFieldsAssociations($table)
    {
        return $this->entityFields->getFieldsAssociations($table);
    }

    public function routeExists($name)
    {
        return (null === $this->router->getRouteCollection()->get($name)) ? false : true;
    }
}
