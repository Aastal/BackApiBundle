<?php

namespace Geoks\AdminBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
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
                    'required' => true,
                    'attr' => [
                        'class' => 'checkbox-animate'
                    ]
                ];
                break;
            case 'date':
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
        $customList = $this->container->getParameter('geoks_admin.ban_fields');
        $autoBan = [
            "created", "created_at", "updated", "updated_at", "passwordRequestedAt", "credentialsExpireAt", "confirmationToken",
            "usernameCanonical", "emailCanonical", "lastLogin", "expired", "expired_at", "credentialsExpired", "token",
            "twoStepVerificationCode", "gcm_token", "expiresAt", "credentialsExpired", "timezone", "createdAt", "updatedAt"
        ];

        $banList = array_merge($customList, $autoBan);

        return $banList;
    }
}
