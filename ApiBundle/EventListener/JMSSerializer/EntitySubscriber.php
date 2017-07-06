<?php
namespace Geoks\ApiBundle\EventListener\JMSSerializer;

use Aws\S3\S3Client;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class EntitySubscriber implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
                        $stringManager = $this->container->get('geoks.utils.string_manager');

                        if ($value) {
                            $path = $annotation->path;
                            $vichMappings = $this->container->getParameter('vich_uploader.mappings');

                            // Check if the project use resize files and map them
                            if ($this->container->hasParameter('liip_imagine') && isset($this->container->getParameter('liip_imagine.filter_sets')["resize_thumb"]) && $sizes = $this->container->getParameter('liip_imagine.filter_sets')["resize_thumb"]["filters"]) {

                                $arraySize = [];
                                foreach ($sizes as $key => $size) {
                                    $arraySize += [$key => $vichMappings[$path]["uri_prefix"] . "/thumb_" . $key . "_" . $stringManager->getEndOfString("/", $value)];
                                }

                                if (method_exists($entity, 'setImageThumbs') && $arraySize) {
                                    $entity->setImageThumbs($arraySize);
                                }
                            }

                            $entity->{'set' . $reflectionProperty->name}(
                                $vichMappings[$path]["uri_prefix"] .
                                '/' .
                                $stringManager->getEndOfString("/", $value)
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
