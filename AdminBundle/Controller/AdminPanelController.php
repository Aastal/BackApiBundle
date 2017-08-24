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
                $groups = $this->jmsGroup($reflection);
                $properties = $this->propertiesType($reflection);

                if ($groups && $properties) {
                    $entities[$name] = [
                        "groups" => $groups,
                        "properties" => $properties
                    ];
                }
            }
        }

        return $this->render("@GeoksAdmin/AdminPanel/index.html.twig", [
            'entities' => $entities
        ]);
    }

    public function logsAction(Request $request)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $paginator  = $this->get('knp_paginator');

        $entities = $em->getRepository('GeoksApiBundle:Log')->findBy(["type" => "front"]);

        $pagination = $paginator->paginate(
            $entities,
            ((count($entities) / 10) < ($request->query->get('page', 1)-1)) ? 1 : $request->query->get('page', 1),
            10, array('wrap-queries' => true)
        );

        return $this->render("@GeoksAdmin/AdminPanel/logs.html.twig", [
            'pagination' => $pagination
        ]);
    }

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    private function jmsGroup($reflection)
    {
        $groups = [];
        $reader = new AnnotationReader();

        foreach ($reflection->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotation($property, "JMS\\Serializer\\Annotation\\Groups")) {

                foreach ($annotation->groups as $group) {

                    if ($annotationColumn = $reader->getPropertyAnnotation($property, "Doctrine\\ORM\\Mapping\\Column")) {
                        $type = $annotationColumn->type;
                    } else {
                        $type = $this->annotationProperty($property);
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
                    } elseif ($annotationAssociation = $reader->getPropertyAnnotation($association, "Doctrine\\ORM\\Mapping\\ManyToOne")) {
                        $type = " (" . $annotationAssociation->targetEntity . ")";
                    } else {
                        $type = null;
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
    private function propertiesType($reflection)
    {
        $properties = [];
        $reader = new AnnotationReader();

        $properties[] = $this->subClass($reader, $reflection);

        return $properties;
    }

    /**
     * @param AnnotationReader $reader
     * @param \ReflectionClass $parent
     * @param array $properties
     * @return array
     */
    private function subClass($reader, $parent, $properties = [])
    {
        foreach ($parent->getProperties() as $property) {
            if ($annotation = $reader->getPropertyAnnotations($property)) {

                $type = $this->annotationProperty($property);

                array_unshift($properties, [
                    'name' => $property->name,
                    'type' => $type
                ]);
            }
        }

        if ($parent = $parent->getParentClass()) {
            $this->subClass($reader, $parent, $properties);
        }

        return $properties;
    }

    /**
     * @param \ReflectionProperty $property
     * @return string
     */
    private function annotationProperty($property)
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