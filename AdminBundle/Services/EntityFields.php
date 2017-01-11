<?php

namespace Geoks\AdminBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
            $findDatas = $this->em->getRepository($cm->getAssociationTargetClass($row))->findAll();

            if (count($findDatas) > 0) {
                $rowAssos[$row] = $cm->getAssociationMapping($row);
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
                    'format' => 'dd/MM/yyyy HH:mm',
                    'attr' => [
                        'class' => 'control-animate datetimepicker'
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
            "twoStepVerificationCode", "gcm_token", "expiresAt", "credentialsExpired", "timezone", "createdAt", "updatedAt",
            "password"
        ];

        $banList = array_merge($customList, $autoBan);

        return $banList;
    }
}
