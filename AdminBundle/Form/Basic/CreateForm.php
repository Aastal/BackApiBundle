<?php

namespace Geoks\AdminBundle\Form\Basic;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Geoks\AdminBundle\Form\Custom\EntityMultipleType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Vich\UploaderBundle\Form\Type\VichFileType;

class CreateForm extends AbstractType
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $container = $options["service_container"];
        $table = $options["data_class"];

        $reader = new AnnotationReader();
        $reflection = new \ReflectionClass($table);
        $banList = $container->get('geoks_admin.entity_fields')->fieldsBanList();

        $this->entityName = strtolower($container->get('geoks_admin.entity_fields')->getEntityName($table));
        $rowArr = $container->get('geoks_admin.entity_fields')->getFieldsName($table);
        $rowAssos = $container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);

        foreach ($rowArr as $name => $field) {
            if ($field["type"] != 'array' && !in_array($name, $banList) && $name != "password") {
                $typeOptions = $container->get('geoks_admin.entity_fields')->switchType($this->entityName, $name, $field["type"]);

                if ((isset($field["nullable"]) && $field["nullable"]) || $field["type"] == 'boolean') {
                    $typeOptions['options']['required'] = false;
                } else {
                    $typeOptions['options']['required'] = true;
                }

                $builder->add($name, $typeOptions['type'], $typeOptions['options']);
            }
        }

        foreach ($reflection->getProperties() as $reflectionProperty) {
            if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Vich\\UploaderBundle\\Mapping\\Annotation\\UploadableField")) {
                $builder
                    ->add($reflectionProperty->name, VichFileType::class, [
                        'label' => $this->entityName . '.' . $reflectionProperty->name,
                        'required' => false,
                        'allow_delete' => true,
                        'download_link' => true,
                        'attr' => [
                            'class' => 'control-animate'
                        ]
                    ])
                ;
            }
        }

        foreach ($rowAssos as $name => $class) {
            if (!in_array($name, $banList)) {

                $typeOptions['options'] = [
                    'label' => $this->entityName . "." . $name,
                    'class' => $class['targetEntity'],
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ];

                if ($class["type"] == 4 || $class["type"] == 8) {
                    $typeOptions['options']['required'] = false;
                    $typeOptions['options']['expanded'] = true;
                    $typeOptions['options']['multiple'] = true;
                    $typeOptions['options']['attr']['class'] = 'multiple';

                    $builder->add($name, EntityMultipleType::class, $typeOptions['options']);
                } else {
                    $builder->add($name, EntityType::class, $typeOptions['options']);
                }
            }
        }

        if (isset($rowArr["password"])) {
            $builder
                ->add('plainPassword', PasswordType::class, [
                    'label' => $this->entityName . '.password',
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ])
            ;
        }
    }

    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ));

        $resolver->setRequired('service_container');
    }
}