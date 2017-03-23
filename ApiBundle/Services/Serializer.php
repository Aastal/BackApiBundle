<?php

namespace Geoks\ApiBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
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
     * @var array
     */
    private $results;

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
        switch ($data)
        {
            case is_array($data):
                foreach ($data as $key => $value) {
                    $this->key = $key;

                    if ($value instanceof ArrayCollection) {
                        $name = $this->getArrayName($value->getValues());
                        $this->results += $this->getArrayValue($name, $value->getValues());
                    } else {
                        $name = $this->getArrayName($value);
                        $this->results += $this->getArrayValue($name, $value);
                    }
                }
                break;
            case is_object($data):
                $name = $this->getArrayName($data);
                $this->results = $this->getArrayValue($name, $data);
                break;
            default:
                $this->results = ['data' => $data];
                break;
        }

        return $this->results;
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

            } elseif (is_array($value) && !is_array(reset($value)) && !is_string(reset($value))) {

                if ($this->pluralize) {
                    $this->entityReflection = new \ReflectionClass(reset($value));
                    $name = $this->entityReflection->getShortName();
                    $name = strtolower($this->container->get('geoks.api.pluralization')->pluralize($name));
                } else {
                    $this->entityReflection = new \ReflectionClass($value);
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
        $reader = new AnnotationReader();

        if (in_array($this->key, $this->groups) && is_string($this->key)) {

            $this->results = [
                $name => $this->serializer->toArray(
                    $value, SerializationContext::create()->setGroups(array($this->key))
                )
            ];

            if ($this->entityReflection) {
                foreach ($this->entityReflection->getProperties() as $reflectionProperty) {
                    if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {

                        $path = $annotation->path;
                        $vichMappings = $this->container->getParameter('vich_uploader.mappings');

                        $this->displayArrayRecursively($this->results, $vichMappings, $path, null);
                    }
                }
            }
        } elseif ($value instanceof \Traversable || is_object($value)) {
            $this->results = [$name => $this->serializer->toArray($value)];
        } else {
            $this->results = [$name => $value];
        }

        return $this->results;
    }

    public function displayArrayRecursively(&$array, $vichMappings, $path, $keysString = '')
    {
        if (is_array($array)) {
            foreach ($array as $key => &$value) {
                if (is_string($key) && $key == "image_name") {
                    $value = $vichMappings[$path]["uri_prefix"] . '/' . $value;
                }

                $this->displayArrayRecursively($value, $vichMappings, $path, $keysString . $key . '.');
            }
        }
    }
}
