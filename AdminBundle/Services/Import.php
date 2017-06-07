<?php

namespace Geoks\AdminBundle\Services;

use AppBundle\Entity\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Import
{
    const INCREMENTAL = 'incremental';
    const REPLACE = 'replace';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ManagerRegistry
     */
    private $em;

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $images;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
    }

    /**
     * @param Form $form
     * @param string $class
     * @param string $dedupeField
     *
     * @return array
     */
    public function importFromCsv($form, $class, $dedupeField = null)
    {
        $file = $form->get('csv')->getData();
        $type = $form->get('type')->getData();

        $this->images = $form->get('images')->getData();

        $data = $this->parseCsv($file);
        $response = $this->importData($data, $class, $type, $dedupeField);

        return $response;
    }

    /**
     * @param $data
     * @param $class
     * @param $type
     * @param $dedupeField
     * @return array
     */
    public function importData($data, $class, $type = null, $dedupeField = null)
    {
        $entities = [];
        $this->class = $class;

        $reader = new AnnotationReader();
        $reflection = new \ReflectionClass($this->class);

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $serializer = new Serializer(array($normalizer));

        $fields = [];
        foreach ($reflection->getProperties() as $reflectionProperty) {
            if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Geoks\\AdminBundle\\Annotation\\ImportField")) {

                if ((isset($annotation->type) && isset($annotation->name)) && $annotation->type == "file") {
                    $fields[$annotation->name] = ['name' => $reflectionProperty->name, 'type' => "file"];
                } elseif (isset($annotation->name)) {
                    $fields[$annotation->name] = ['name' => $reflectionProperty->name, 'type' => "string"];
                }
            }
        }

        if (array_key_exists("0", $data)) {
            foreach ($data as $item) {

                foreach ($item as $key => &$value) {

                    if ($value) {
                        if (!is_object($value) && $this->container->get('geoks.utils.string_manager')->validateDate($value)) {
                            $value = $this->container->get('geoks.utils.string_manager')->validateDate($value);
                        }

                        if (!empty($fields) && array_key_exists($key, $fields)) {

                            if ($fields[$key]['type'] == 'string') {
                                $item[key($fields[$key])] = $value;
                                unset($item[$key]);
                            }
                        } elseif (!empty($fields)) {

                            foreach ($fields as $k => $field) {
                                if ($key == $field['name']) {
                                    $item[$k] = $value;
                                }
                            }

                        }
                    } else {
                        $rc = new \ReflectionClass($this->class);
                        $setter = 'set' . ucfirst(key($fields[$key]));

                        if (!empty($fields) && array_key_exists($key, $fields)) {
                            if ($rc->hasMethod($setter)) {
                                if ($docs = $rc->getMethod($setter)->getDocComment()) {
                                    if (strpos($docs, "@param datetime") || strpos($docs, "@param \\DateTime")) {
                                        $value = new \DateTime(null);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($this->images && reset($this->images) instanceof UploadedFile) {
                    foreach ($this->images as $image) {

                        /** @var UploadedFile $image */
                        $string = explode(".", $image->getClientOriginalName());
                        $string = $string[0];

                        foreach ($fields as $key => $v) {

                            if ($item[$key] == $string) {
                                $item[$key] = $image;
                            }
                        }
                    }
                }

                foreach ($fields as $key => &$value) {
                    if (isset($item[$key]) && !$item[$key] instanceof File && $value['type'] == 'file') {
                        $item[$key] = null;
                    }
                }

                $entities[] = $serializer->denormalize($item, $this->class);
            }
        } else {
            $entities[] = $serializer->denormalize($data, $this->class);
        }

        $this->__insertByType($entities, $type, $dedupeField);

        return ["success" => true];
    }

    public function parseCsv($file)
    {
        $header = null;
        $data = [];

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, null, ";")) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    foreach ($header as &$h) {
                        $h = trim(lcfirst($h));
                    }

                    $data[] = array_combine($header, $row);
                }
            }

            fclose($handle);
        }

        return $data;
    }

    private function __insertByType($entities, $type, $dedupeField)
    {
        $fieldsAssociations = $this->container->get('geoks_admin.entity_fields')->getFieldsAssociations($this->class);
        $fieldsAssociations = new ArrayCollection($fieldsAssociations);

        if ($type == 'replace') {
            $currentEntities = $this->em->getRepository($this->class)->findAll();
            $currentEntities = new ArrayCollection($currentEntities);

            foreach ($currentEntities as $currentEntity) {
                $this->em->remove($currentEntity);
            }
        }

        foreach ($entities as $entity) {

            if ($dedupeField) {
                if (is_array($dedupeField)) {

                    $fields = [];
                    foreach ($dedupeField as $d) {
                        $value = $entity->{"get" . ucfirst($d)}();

                        if (is_object($value)) {
                            $value = $value->getId();
                        }

                        $fields += [
                            $d => $value
                        ];
                    }

                    $oldEntity = $this->em->getRepository($this->class)->findOneBy($fields);
                } else {
                    $value = $entity->{"get" . ucfirst($dedupeField)}();

                    if (is_object($value)) {
                        $value = $value->getId();
                    }

                    $oldEntity = $this->em->getRepository($this->class)->findOneBy([$dedupeField => $value]);
                }

                if (isset($oldEntity)) {
                    $this->em->remove($oldEntity);
                }
            }

            $this->em->persist($entity);

            $fieldsAssociations->filter(function ($entry) use ($entity) {

                if (method_exists($entity, 'get' . ucfirst($entry["fieldName"]))) {
                    $getter = $entity->{'get' . ucfirst($entry["fieldName"])}();

                    if (method_exists($entity, 'set' . ucfirst($entry["fieldName"]))) {
                        if (is_string($getter)) {
                            if (strpos($getter, ",")) {
                                $array = explode(",", $getter);
                                $collection = new ArrayCollection();

                                foreach ($array as $item) {
                                    if ($assoc = $this->em->getRepository($entry["targetEntity"])->find($item)) {
                                        $collection->add($assoc);
                                    }
                                }

                                $entity->{'set' . ucfirst($entry["fieldName"])}($collection);
                            } else {
                                if ($assoc = $this->em->getRepository($entry["targetEntity"])->find($getter)) {
                                    $rc = new \ReflectionClass($this->class);

                                    if ($docs = $rc->getMethod('set' . ucfirst($entry["fieldName"]))->getDocComment()) {
                                        if (strpos($docs, "@param Collection") || strpos($docs, "@param ArrayCollection")) {
                                            $assoc = new ArrayCollection([$assoc]);
                                        }
                                    }

                                    $entity->{'set' . ucfirst($entry["fieldName"])}($assoc);
                                }
                            }
                        }
                    }
                }
            });

            $this->manageException($entity);
        }

        $this->em->flush();
        $this->em->clear();
    }

    private function manageException($entity)
    {
        if ($this->container->hasParameter('geoks_admin.import.directories')) {
            foreach ($this->container->getParameter('geoks_admin.import.directories') as $dir) {
                foreach ($dir['exceptions'] as $exception) {
                    $exceptionClass = new $dir['service']($this->container);
                    $exceptionClass->{"manage" . ucfirst($exception)}($entity, $this->class);
                }
            }
        }
    }
}