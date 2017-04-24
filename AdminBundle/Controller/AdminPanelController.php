<?php

namespace Geoks\AdminBundle\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AdminPanelController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $meta = $this->getDoctrine()->getManager()->getMetadataFactory()->getAllMetadata();

        $entities = [];
        foreach ($meta as $m) {

            /** @var ClassMetadata $m */
            if (!$m->getReflectionClass()->isAbstract()) {
                $reflection = $m->getReflectionClass();

                $name = $reflection->getShortName();
                $groups = $this->__jmsGroup($reflection);
                $properties = $this->__propertiesType($reflection);

                if ($groups && $properties) {
                    $entities[$name] = [
                        "groups" => $groups,
                        "properties" => $properties
                    ];
                }
            }
        }

        return $this->render("@GeoksAdmin/adminPanel/index.html.twig", [
            'entities' => $entities
        ]);
    }

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    private function __jmsGroup($reflection)
    {
        $groups = [];
        $reader = new AnnotationReader();

        foreach ($reflection->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation($property, "JMS\\Serializer\\Annotation\\Groups")) {

                foreach ($annotation->groups as $group) {

                    if ($annotationColumn = $reader->getPropertyAnnotation($property, "Doctrine\\ORM\\Mapping\\Column")) {
                        $type = $annotationColumn->type;
                    } else {
                        $type = $this->__annotationProperty($property);
                    }

                    if ($annotationAssociation = $reader->getPropertyAnnotation($property, "Doctrine\\ORM\\Mapping\\OneToMany")) {
                        $association = " (" . $annotationAssociation->targetEntity . ")";
                    } elseif ($annotationAssociation = $reader->getPropertyAnnotation($property, "Doctrine\\ORM\\Mapping\\ManyToMany")) {
                        $association = " (" . $annotationAssociation->targetEntity . ")";
                    } elseif ($annotationAssociation = $reader->getPropertyAnnotation($property, "Doctrine\\ORM\\Mapping\\ManyToOne")) {
                        $association = " (" . $annotationAssociation->targetEntity . ")";
                    } else {
                        $association = null;
                    }

                    $groups[$group][] = [
                        "name" => $this->container->get('geoks.utils.string_manager')->fromCamelCase($property->name),
                        "type" => $type . $association
                    ];
                }
            }
        }

        $associations = $this->container->get('geoks_admin.entity_fields')->getFieldsAssociations($reflection->name);

        foreach ($associations as $association) {
            $association = new \ReflectionProperty($reflection->name, $association["fieldName"]);

            if ($annotation = $reader->getPropertyAnnotation($association, "JMS\\Serializer\\Annotation\\Groups")) {
                foreach ($annotation->groups as $group) {

                    if ($annotationAssociation = $reader->getPropertyAnnotation($association, "Doctrine\\ORM\\Mapping\\OneToMany")) {
                        $type = " (" . $annotationAssociation->targetEntity . ")";
                    } elseif ($annotationAssociation = $reader->getPropertyAnnotation($association, "Doctrine\\ORM\\Mapping\\ManyToMany")) {
                        $type = " (" . $annotationAssociation->targetEntity . ")";
                    } else {
                        $type = " (" . $annotationAssociation->targetEntity . ")";
                    }

                    $groups[$group][] = [
                        "name" => $this->container->get('geoks.utils.string_manager')->fromCamelCase($association->name),
                        "type" => $type
                    ];
                }
            }
        }

        return $groups;
    }

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    private function __propertiesType($reflection)
    {
        $properties = [];
        $reader = new AnnotationReader();

        $properties[] = $this->__subClass($reader, $reflection);

        return $properties;
    }

    /**
     * @param AnnotationReader $reader
     * @param \ReflectionClass $parent
     * @param array $properties
     * @return array
     */
    private function __subClass($reader, $parent, $properties = [])
    {
        foreach ($parent->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotations($property)) {

                $type = $this->__annotationProperty($property);

                array_unshift($properties, [
                    'name' => $property->name,
                    'type' => $type
                ]);
            }
        }

        if ($parent = $parent->getParentClass()) {
            $this->__subClass($reader, $parent, $properties);
        }

        return $properties;
    }

    /**
     * @param \ReflectionProperty $property
     * @return string
     */
    private function __annotationProperty($property)
    {
        if (strpos($property->getDocComment(), "@var string"))
            $type = "string";
        elseif (strpos($property->getDocComment(), "@var \\DateTime"))
            $type = "datetime";
        elseif (strpos($property->getDocComment(), "@var datetime"))
            $type = "datetime";
        elseif (strpos($property->getDocComment(), "@var int"))
            $type = "int";
        elseif (strpos($property->getDocComment(), "@var integer"))
            $type = "integer";
        elseif (strpos($property->getDocComment(), "@var decimal"))
            $type = "decimal";
        elseif (strpos($property->getDocComment(), "@var float"))
            $type = "float";
        elseif (strpos($property->getDocComment(), "@var mixed"))
            $type = "mixed";
        elseif (strpos($property->getDocComment(), "@var ArrayCollection"))
            $type = "array";
        elseif (strpos($property->getDocComment(), "@var array"))
            $type = "array";
        elseif (strpos($property->getDocComment(), "@var Collection"))
            $type = "array";
        elseif (strpos($property->getDocComment(), "@var File"))
            $type = "file";
        else
            $type = "relation";

        return $type;
    }
}