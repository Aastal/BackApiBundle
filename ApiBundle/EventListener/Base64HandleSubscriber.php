<?php
namespace Geoks\ApiBundle\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Base64HandleSubscriber implements EventSubscriber
{
    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'preUpdate',
            'prePersist'
        );
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        try
        {
            $classReflection = new \ReflectionClass($entity);
        }
        catch (\Exception $exception)
        {
            $classReflection = null;
        }

        if ($classReflection) {
            $reader = new AnnotationReader();

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

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        try
        {
            $classReflection = new \ReflectionClass($entity);
        }
        catch (\Exception $exception)
        {
            $classReflection = null;
        }

        if ($classReflection) {
            $reader = new AnnotationReader();

            foreach ($classReflection->getProperties() as $reflectionProperty) {
                if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\Base64Handler")) {
                    $entity->{'set' . ucfirst($reflectionProperty->name)}(base64_encode($entity->{'get' . ucfirst($reflectionProperty->name)}()));
                }
            }
        }
    }
}
