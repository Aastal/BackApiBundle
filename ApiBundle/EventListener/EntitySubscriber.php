<?php
namespace Geoks\ApiBundle\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Util\ClassUtils;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            ),
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

            if ($reader->getClassAnnotation($classReflection, "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {
                foreach ($classReflection->getProperties() as $reflectionProperty) {
                    if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\FilePath")) {
                        $value = $entity->{'get' . $reflectionProperty->name}();

                        if ($value) {
                            $path = $annotation->path;
                            $vichMappings = $this->container->getParameter('vich_uploader.mappings');

                            $entity->{'set' . $reflectionProperty->name}(
                                $vichMappings[$path]["uri_prefix"] .
                                '/' .
                                $this->container->get('geoks.utils.string_manager')->getEndOfString("/", $value)
                            );
                        }
                    }
                }
            }
        }
    }
}
