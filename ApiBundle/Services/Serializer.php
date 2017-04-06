<?php

namespace Geoks\ApiBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\Serializer\Serializer as JMSSerializer;
use Symfony\Component\Yaml\Parser;

class Serializer
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var JMSSerializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var boolean
     */
    private $pluralize;

    /**
     * @var \ReflectionClass
     */
    private $entityReflection;

    /**
     * Serializer constructor.
     *
     * @param ContainerInterface $container
     * @param JMSSerializer $serializer
     * @param $pluralize
     */
    public function __construct(ContainerInterface $container, JMSSerializer $serializer, $pluralize)
    {
        $this->container = $container;
        $this->serializer = $serializer;
        $this->pluralize = $pluralize;
        $this->groups = $this->container->getParameter('geoks_api.jms_groups');
    }

    /**
     * @param $data
     * @return array
     */
    public function serializeData($data)
    {
        $results = [];

        switch ($data)
        {
            case is_array($data):
                foreach ($data as $key => $value) {
                    $this->key = $key;

                    if ($value instanceof ArrayCollection) {
                        $name = $this->getArrayName($value->getValues());
                        $results += $this->getArrayValue($name, $value->getValues());
                    } else {
                        $name = $this->getArrayName($value);
                        $results += $this->getArrayValue($name, $value);
                    }
                }
                break;
            case is_object($data):
                $name = $this->getArrayName($data);
                $results += $this->getArrayValue($name, $data);
                break;
            default:
                $results = ['data' => $data];
                break;
        }

        return $results;
    }

    /**
     * @param $data
     * @return array
     */
    public function simpleSerializeData($data)
    {
        $results = [];

        switch ($data)
        {
            case is_array($data):
                foreach ($data as $key => $value) {
                    $this->key = $key;

                    if ($value instanceof ArrayCollection) {
                        $results += $this->getArrayValue($key, $value->getValues());
                    } else {
                        $results += $this->getArrayValue($key, $value);
                    }
                }
                break;
            case is_object($data):
                $results += $this->getArrayValue(key($data), $data);
                break;
            default:
                $results = ['data' => $data];
                break;
        }

        return $results;
    }

    /**
     * @param $value
     * @return string
     */
    private function getArrayName($value)
    {
        if ($value) {
            if (is_object($value)) {

                $this->entityReflection = new \ReflectionClass($value);
                $name = strtolower($this->entityReflection->getShortName());

            } elseif (is_array($value) && !is_array(reset($value)) && !is_string(reset($value)) && !is_bool(reset($value))) {

                if ($this->pluralize) {
                    $this->entityReflection = new \ReflectionClass(reset($value));
                    $name = $this->entityReflection->getShortName();
                    $name = strtolower($this->container->get('geoks.api.pluralization')->pluralize($name));
                } else {
                    $this->entityReflection = new \ReflectionClass(reset($value));
                    $name = $this->entityReflection->getShortName();
                    $name = strtolower($name . "s");
                }

            } else {
                $name = $this->key;
            }
        } else {
            if ($this->key) {
                $name = $this->key;
            } else {
                $name = 'data';
            }
        }

        return $name;
    }

    /**
     * @param $name
     * @param $value
     * @return array
     */
    private function getArrayValue($name, $value)
    {
        if (in_array($this->key, $this->groups) && is_string($this->key)) {
            $results = [
                $name => $this->serializer->toArray(
                    $value, SerializationContext::create()->setGroups(array($this->key))
                )
            ];
        } elseif ($value instanceof \Traversable || is_object($value)) {
            $results = [$name => $this->serializer->toArray($value)];
        } else {
            $results = [$name => $value];
        }

        $this->imageArrayKey($results);
        $this->arrayImagesPathsRecursively($results);

        return $results;
    }

    private function imageArrayKey(&$array)
    {
        $reader = new AnnotationReader();

        if (isset($this->entityReflection)) {
            foreach ($this->entityReflection->getProperties() as $reflectionProperty) {
                if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {
                    $path = $annotation->path;
                    $vichMappings = $this->container->getParameter('vich_uploader.mappings');
                }
            }

            if (isset($vichMappings) && isset($path)) {
                foreach ($array as &$r) {
                    if (is_array($r) && array_key_exists("image_name", $r)) {
                        $r["image_name"] = $vichMappings[$path]["uri_prefix"] .
                            '/' .
                            $this->container->get('geoks.utils.string_manager')->getEndOfString("/", $r["image_name"]);
                    }

                    if (is_array($r)) {
                        foreach ($r as &$v) {
                            if (is_array($v) && array_key_exists("image_name", $v)) {
                                $v["image_name"] = $vichMappings[$path]["uri_prefix"] .
                                    '/' .
                                    $this->container->get('geoks.utils.string_manager')->getEndOfString("/", $v["image_name"]);
                            }
                        }
                    }
                }
            }
        }
    }

    private function arrayImagesPathsRecursively(&$array, $keysString = '')
    {
        if (is_array($array)) {
            $reflections = [];
            $reader = new AnnotationReader();
            $meta = $this->container->get('doctrine')->getManager()->getMetadataFactory()->getAllMetadata();

            foreach ($meta as $m) {

                /** @var ClassMetadata $m */
                $pos = strpos($m->getName(), "FOS\\UserBundle\\Model\\User");
                $pos2 = strpos($m->getName(), "Geoks\\UserBundle\\Entity\\User");

                if ($pos === false && $pos2 === false) {
                    $reflections[] = $m->getReflectionClass();
                }
            }

            foreach ($array as $key => &$value) {

                $arrayWords = explode("_", $key);

                foreach ($arrayWords as $k => &$v) {
                    $v = ucfirst($v);
                }

                $class = implode("", $arrayWords);

                foreach ($reflections as $reflection) {
                    /** @var \ReflectionClass $reflection */
                    if ($reflection->getShortName() == $class) {
                        /** @var \ReflectionClass $classReflection */
                        $classReflection = $reflection;
                    }
                }

                $path = null;
                $vichMappings = null;

                if (isset($classReflection)) {
                    foreach ($classReflection->getProperties() as $reflectionProperty) {
                        if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {

                            $path = $annotation->path;
                            $vichMappings = $this->container->getParameter('vich_uploader.mappings');
                        }
                    }

                    if (is_array($value) && array_key_exists("image_name", $value) && $path) {
                        $value["image_name"] = $vichMappings[$path]["uri_prefix"] .
                            '/' .
                            $this->container->get('geoks.utils.string_manager')->getEndOfString("/", $value["image_name"]);
                    }
                }

                $this->arrayImagesPathsRecursively($value, $keysString . $key . '.');
            }
        }
    }
}
