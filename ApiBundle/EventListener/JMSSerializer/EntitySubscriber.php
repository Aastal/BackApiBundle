<?php
namespace Geoks\ApiBundle\EventListener\JMSSerializer;

use Aws\S3\S3Client;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;
use Geoks\ApiBundle\Utils\StringUtils;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class EntitySubscriber implements EventSubscriberInterface
{
    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * @var array
     */
    private $vichMappings;

    /**
     * @var array
     */
    private $filterSets;

    /**
     * @param StringUtils $stringUtils
     * @param array $vichMappings
     */
    public function __construct(StringUtils $stringUtils, $vichMappings, $filterSets)
    {
        $this->stringUtils = $stringUtils;
        $this->vichMappings = $vichMappings;
        $this->filterSets = $filterSets;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize'
            )
        );
    }

    /**
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        /** @var object $entity */
        $entity = $event->getObject();

        try
        {
            $classReflection = ClassUtils::newReflectionObject($entity);
        }
        catch (\Exception $exception)
        {
            $classReflection = null;
        }

        if ($classReflection) {
            $reader = new AnnotationReader();

            // Check if the entity can upload a file
            if ($reader->getClassAnnotation($classReflection, "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {

                foreach ($classReflection->getProperties() as $reflectionProperty) {
                    if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {

                        $value = $entity->{'get' . $reflectionProperty->name}();

                        if ($value) {
                            $path = $annotation->path;

                            // Check if the project use resize files and map them
                            if (isset($this->filterSets["resize_thumb"]) && $sizes = $this->filterSets["resize_thumb"]["filters"]) {

                                $arraySize = [];
                                foreach ($sizes as $key => $size) {
                                    $arraySize += [$key => $this->vichMappings[$path]["uri_prefix"] . "/thumb_" . $key . "_" . $this->stringUtils->getEndOfString("/", $value)];
                                }

                                if (method_exists($entity, 'setImageThumbs') && $arraySize) {
                                    $entity->setImageThumbs($arraySize);
                                }
                            }

                            $entity->{'set' . $reflectionProperty->name}(
                                $this->vichMappings[$path]["uri_prefix"] .
                                '/' .
                                $this->stringUtils->getEndOfString("/", $value)
                            );
                        }
                    }
                }
            }

            if ($reader->getClassAnnotation($classReflection, "Geoks\\ApiBundle\\Annotation\\Base64Check")) {
                foreach ($classReflection->getProperties() as $reflectionProperty) {
                    if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\Base64Handle")) {

                        $property = $entity->{'get' . $reflectionProperty->name}();

                        if ((base64_encode(base64_decode($property, true)) === $property) && $property != "test" && $property != "true") {
                            $entity->{'set' . $reflectionProperty->name}(base64_decode($property));
                        }
                    }
                }
            }
        }
    }
}
