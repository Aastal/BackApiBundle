<?php
namespace Geoks\ApiBundle\EventListener\Doctrine;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

class EntitySubscriber implements EventSubscriber
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

        $this->postLoadBase64($entity);
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

        $this->manageBase64($entity);
    }

    private function manageBase64($entity)
    {
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

            if ($reader->getClassAnnotation($classReflection, "Geoks\\ApiBundle\\Annotation\\Base64Check")) {
                foreach ($classReflection->getProperties() as $reflectionProperty) {
                    if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\ApiBundle\\Annotation\\Base64Handle")) {
                        $entity->{'set' . ucfirst($reflectionProperty->name)}(base64_encode($entity->{'get' . ucfirst($reflectionProperty->name)}()));
                    }
                }
            }
        }
    }

    private function postLoadBase64($entity)
    {
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

                        $banWords = ["test", "Test", "TEST", "true", "True", "TRUE"];
                        $property = $entity->{'get' . $reflectionProperty->name}();

                        if ((base64_encode(base64_decode($property, true)) === $property) && !in_array($property, $banWords)) {
                            $entity->{'set' . $reflectionProperty->name}(base64_decode($property));
                        }
                    }
                }
            }
        }
    }
}
