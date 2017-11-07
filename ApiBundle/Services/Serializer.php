<?php

namespace Geoks\ApiBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
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
            if (is_object($value) && !$value instanceof PersistentCollection) {

                $this->entityReflection = new \ReflectionClass($value);
                $name = strtolower($this->entityReflection->getShortName());
            } elseif ($value instanceof PersistentCollection) {
                $name = $this->getEntityName($value->getMapping()["targetEntity"]);
            } elseif (is_array($value) && !is_array(reset($value)) && !is_string(reset($value)) && !is_bool(reset($value)) && !is_numeric(reset($value))) {
                $name = $this->getEntityName(reset($value));
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

        return $results;
    }

    private function getEntityName($value)
    {
        if ($this->pluralize) {
            $this->entityReflection = new \ReflectionClass($value);
            $name = $this->entityReflection->getShortName();
            $name = strtolower($this->container->get('geoks.utils.string_manager')->pluralize(strtolower($name)));
        } else {
            $this->entityReflection = new \ReflectionClass($value);
            $name = $this->entityReflection->getShortName();
            $name = strtolower($name . "s");
        }

        return $name;
    }
}
