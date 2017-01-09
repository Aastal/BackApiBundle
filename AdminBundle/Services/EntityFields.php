<?php

namespace Geoks\AdminBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EntityFields
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getEntityName($table)
    {
        return (new \ReflectionClass($table))->getShortName();
    }

    public function getFieldsName($table)
    {
        $rowArr = [];
        $excArr = ['id', 'salt'];

        $cm = $this->em->getClassMetadata($table);
        $rows = $cm->getFieldNames();

        foreach ($rows as $row) {
            if (!in_array($row, $excArr)) {
                $rowArr[$row] = $cm->getFieldMapping($row);
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
            $rowAssos[$row] = $cm->getAssociationMapping($row);
        }

        return $rowAssos;
    }

    public function switchType($entityName, $name, $type)
    {
        $r = [];
        $fieldName = $entityName . "." . $name;

        switch ($type) {
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
                    'required' => false,
                    'attr' => [
                        'class' => 'checkbox-animate'
                    ]
                ];
                break;
            case 'datetime':
                $r['type'] = DateTimeType::class;
                $r['options'] = [
                    'label' => $fieldName,
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm:ssZ',
                    'attr' => [
                        'class' => 'control-animate'
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
}
