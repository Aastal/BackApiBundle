<?php

namespace Geoks\AdminBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
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
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
    }

    /**
     * @param File $file
     * @param string $class
     * @param string $type
     *
     * @return array
     */
    public function importFromCsv($file, $class, $type = null)
    {
        $entities = [];
        $this->class = $class;

        $data = $this->__parseCsv($file);
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $serializer = new Serializer(array($normalizer));

        if (array_key_exists("0", $data)) {
            foreach ($data as $item) {

                foreach ($item as $key => &$value) {
                    if (!$value) {

                        return [
                            "success" => false,
                            "error" => "empty_or_wrong_value"
                        ];
                    } elseif ($this->container->get('geoks.utils.string_manager')->validateDate($value)) {
                        $value = $this->container->get('geoks.utils.string_manager')->validateDate($value);
                    }
                }

                $entities[] = $serializer->denormalize($item, $this->class);
            }
        } else {
            $entities[] = $serializer->denormalize($data, $this->class);
        }

        $this->__insertByType($entities, $type);

        return ["success" => true];
    }

    private function __parseCsv($file)
    {
        $header = null;
        $data = [];

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, null, ";")) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }

            fclose($handle);
        }

        return $data;
    }

    private function __insertByType($entities, $type)
    {
        $fieldsAssociations = $this->container->get('geoks_admin.entity_fields')->getFieldsAssociations($this->class);
        $fieldsAssociations = new ArrayCollection($fieldsAssociations);


        if ($type == 'replace') {
            $currentEntities = $this->em->getRepository($this->class)->findAll();
            $currentEntities = new ArrayCollection($currentEntities);

            foreach ($currentEntities as $currentEntity) {
                $this->em->remove($currentEntity);
            }

            $this->em->flush();
        }

        foreach ($entities as $entity) {
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
        }

        $this->em->flush();
    }
}