<?php

namespace Geoks\AdminBundle\Form\Basic;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Geoks\AdminBundle\Form\Custom\EntityMultipleType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
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
        /** @var ContainerInterface $container */
        $container = $options["service_container"];
        $table = $options["data_class"];

        /** @var Translator $translator */
        $translator = $container->get('translator');

        $reader = new AnnotationReader();
        $reflection = new \ReflectionClass($table);
        $banList = $container->get('geoks_admin.entity_fields')->fieldsBanList();

        $this->entityName = strtolower($container->get('geoks_admin.entity_fields')->getEntityName($table));
        $rowArr = $container->get('geoks_admin.entity_fields')->getFieldsName($table);
        $rowAssos = $container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);

        foreach ($rowArr as $name => $field) {

            if (isset($field["type"]) && !in_array($name, $banList)) {
                $typeOptions = $container->get('geoks_admin.entity_fields')->switchType($this->entityName, $name, $field["type"]);

                if ((isset($field["nullable"]) && $field["nullable"]) || $field["type"] == 'boolean') {
                    $typeOptions['options']['required'] = false;
                } else {
                    $typeOptions['options']['required'] = true;
                }

                if ($annotation = $container->get('geoks_admin.entity_fields')->checkAnnotation($reflection, $name, "Geoks\\AdminBundle\\Annotation\\ChoiceList", "Geoks\\AdminBundle\\Annotation\\HasChoiceField")) {
                    $reflectionProperty = $reflection->getProperty($name);

                    if ($name == $reflectionProperty->name) {
                        $typeOptions['type'] = ChoiceType::class;
                        $typeOptions['options']['choices'] = [];

                        foreach ($annotation->choices as $choice) {
                            $typeOptions['options']['choices'] += [$choice => $translator->trans($choice)];
                        }
                    }
                }

                if ($name == 'roles' && $container->get('security.token_storage')->getToken()->getUser()->hasRole("ROLE_SUPER_ADMIN")) {
                    $builder->add($name, $typeOptions['type'], $typeOptions['options']);
                } elseif ($name != 'roles') {
                    $builder->add($name, $typeOptions['type'], $typeOptions['options']);
                }
            }
        }

        if ($reader->getClassAnnotation($reflection, "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {
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
        }

        foreach ($rowAssos as $name => $class) {
            if (!in_array($name, $banList)) {

                $typeOptions['options'] = [
                    'label' => $this->entityName . "." . $name,
                    'class' => $class['targetEntity'],
                    'required' => false,
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ];

                if ($annotation = $reader->getPropertyAnnotation($reflection->getProperty($name), "Symfony\\Component\\Validator\\Constraints\\NotNull")) {
                    $typeOptions['options']['required'] = true;
                }

                if ($class["type"] == 8) {
                    $typeOptions['options']['expanded'] = true;
                    $typeOptions['options']['multiple'] = true;
                    $typeOptions['options']['attr']['class'] = 'multiple';
                    $typeOptions['options']['label_attr']['class'] = 'label-multiple';

                    $builder->add($name, EntityMultipleType::class, $typeOptions['options']);

                } elseif ($class["type"] != 4) {
                    $builder->add($name, EntityType::class, $typeOptions['options']);
                }
            }
        }

        if (isset($rowArr["password"])) {
            $builder
                ->remove('password')
                ->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'first_options' => [
                        'label' => $this->entityName . '.password',
                        'attr' => [
                            'class' => 'control-animate'
                        ]
                    ],
                    'second_options' => [
                        'label' => $this->entityName . '.confirmPassword',
                        'attr' => [
                            'class' => 'control-animate'
                        ]
                    ],
                    'invalid_message' => 'geoks.password.mismatch',
                    'attr' => [
                        'class' => 'form-horizontal'
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
