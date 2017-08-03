<?php

namespace Geoks\AdminBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EntityFields
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $banFields;

    /**
     * @param EntityManager $em
     * @param array $banFields
     */
    public function __construct(EntityManager $em, $banFields)
    {
        $this->em = $em;
        $this->banFields = $banFields;
    }

    public function getEntityName($table)
    {
        return (new \ReflectionClass($table))->getShortName();
    }

    public function getImageFields($entity)
    {
        $properties = [];
        $reader = new AnnotationReader();

        $cm = $this->em->getClassMetadata(get_class($entity))->getReflectionClass();

        if ($reader->getClassAnnotation($cm, "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {

            foreach ($cm->getProperties() as $reflectionProperty) {
                if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Vich\\UploaderBundle\\Mapping\\Annotation\\UploadableField")) {
                    $properties[$reflectionProperty->getName()] = $annotation;
                }
            }
        }
        return $properties;
    }

    public function getFieldsName($table, $isRequired = false)
    {
        $rowArr = [];

        $cm = $this->em->getClassMetadata($table);
        $rows = $cm->getFieldNames();
        $rows = array_diff($rows, ['id', 'salt']);

        foreach ($rows as $row) {
            if ($isRequired) {
                if (!$cm->isNullable($row) && $cm->getFieldMapping($row)["type"] != "boolean") {
                    $rowArr[$row] = $cm->getFieldMapping($row);
                }
            } else {
                $rowArr[$row] = $cm->getFieldMapping($row);
            }
        }

        return $rowArr;
    }

    public function getFieldsByName($table, $fields)
    {
        $rowArr = [];

        $cm = $this->em->getClassMetadata($table);

        foreach ($fields as $field) {
            if ($cm->hasField($field)) {
                $rowArr[$field] = $cm->getFieldMapping($field);
            }
        }

        return $rowArr;
    }

    public function getFieldsAssociations($table)
    {
        $rowAssos = [];

        $cm = $this->em->getClassMetadata($table);
        $rows = $cm->getAssociationNames();

        foreach ($rows as $row) {
            $targetClass = $cm->getAssociationTargetClass($row);
            $reflection = new \ReflectionClass($targetClass);

            if (!$reflection->isAbstract()) {
                $findDatas = $this->em->getRepository($targetClass)->findAll();

                if (count($findDatas) > 0) {
                    $rowAssos[$row] = $cm->getAssociationMapping($row);
                }
            }
        }

        return $rowAssos;
    }

    /**
     * @param \ReflectionClass $reflection
     * @param string $property
     * @param string $annotation
     * @param string|null $parentAnnotation
     * @param array|null $result
     * @return array
     */
    public function checkAnnotation($reflection, $property, $annotation, $parentAnnotation = null, $result = null)
    {
        $reader = new AnnotationReader();

        if ($parentAnnotation) {
            if ($reader->getClassAnnotation($reflection, $parentAnnotation)) {
                if ($reflection->hasProperty($property) && $result = $reader->getPropertyAnnotation($reflection->getProperty($property), $annotation)) {
                    return $result;
                }
            }
        } else {
            if ($reflection->hasProperty($property) && $result = $reader->getPropertyAnnotation($reflection->getProperty($property), $annotation)) {
                return $result;
            }
        }

        if ($reflection->getParentClass()) {
            $result = $this->checkAnnotation($reflection->getParentClass(), $property, $annotation, $parentAnnotation, $result);
        }

        return $result;
    }

    public function switchType($entityName, $name, $type)
    {
        $r = [];
        $fieldName = $entityName . "." . $name;

        switch ($type) {
            case 'phone_number':
                $r['type'] = PhoneNumberType::class;
                $r['options'] = [
                    'default_region' => 'FR',
                    'format' => PhoneNumberFormat::INTERNATIONAL,
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ];
                break;
            case 'integer':
                $r['type'] = IntegerType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ];
                break;
            case 'boolean':
                $r['type'] = CheckboxType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'attr' => [
                        'class' => 'checkbox-animate'
                    ]
                ];
                break;
            case 'date':
                $r['type'] = DateType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'widget' => 'single_text',
                    'required' => false,
                    'format' => 'dd/MM/yyyy',
                    'attr' => [
                        'class' => 'control-animate datepicker'
                    ]
                ];
                break;
            case 'datetime':
                $r['type'] = DateTimeType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'widget' => 'single_text',
                    'required' => false,
                    'format' => 'dd/MM/yyyy HH:mm',
                    'attr' => [
                        'class' => 'control-animate datetimepicker'
                    ]
                ];
                break;
            case 'text':
                $r['type'] = TextareaType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ];
                break;
            case 'array':
                $r['type'] = ChoiceType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'choices' => null,
                    'expanded' => true,
                    'multiple' => true,
                    'attr' => [
                        'class' => 'control-animate choices-list'
                    ]
                ];
                break;
            default:
                $r['type'] = TextType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ];
                break;
        }

        return $r;
    }

    public function fieldsBanList()
    {
        $autoBan = [
            "created", "created_at", "updated", "updated_at", "passwordRequestedAt", "credentialsExpireAt", "confirmationToken",
            "usernameCanonical", "emailCanonical", "lastLogin", "expired", "expired_at", "credentialsExpired", "token",
            "twoStepVerificationCode", "gcm_token", "expiresAt", "credentialsExpired", "timezone", "createdAt", "updatedAt"
        ];

        $banList = array_merge($this->banFields, $autoBan);

        return $banList;
    }
}
