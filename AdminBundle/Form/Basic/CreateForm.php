<?php

namespace Geoks\AdminBundle\Form\Basic;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Geoks\AdminBundle\Form\Custom\EntityMultipleType;
use Geoks\AdminBundle\Services\EntityFields;
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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
        /** @var EntityFields $entityFields */
        $entityFields = $options["entity_fields"];
        $table = $options["data_class"];

        /** @var Translator $translator */
        $translator = $options['translator'];

        $reader = new AnnotationReader();
        $reflection = new \ReflectionClass($table);
        $banList = $entityFields->fieldsBanList();

        $this->entityName = strtolower($entityFields->getEntityName($table));
        $rowArr = $entityFields->getFieldsName($table);
        $rowAssos = $entityFields->getFieldsAssociations($table);

        foreach ($rowArr as $name => $field) {

            if (isset($field["type"]) && !in_array($name, $banList)) {
                $typeOptions = $entityFields->switchType($this->entityName, $name, $field["type"]);

                if ((isset($field["nullable"]) && $field["nullable"]) || $field["type"] == 'boolean') {
                    $typeOptions['options']['required'] = false;
                } else {
                    $typeOptions['options']['required'] = true;
                }

                if ($annotation = $entityFields->checkAnnotation($reflection, $name, "Geoks\\AdminBundle\\Annotation\\ChoiceList", "Geoks\\AdminBundle\\Annotation\\HasChoiceField")) {
                    $reflectionProperty = $reflection->getProperty($name);

                    if ($name == $reflectionProperty->name) {
                        $typeOptions['type'] = ChoiceType::class;
                        $typeOptions['options']['choices'] = [];

                        foreach ($annotation->choices as $choice) {
                            $typeOptions['options']['choices'] += [$choice => $translator->trans($choice)];
                        }
                    }
                }

                if ($name == 'roles' && $options['current_user']->hasRole("ROLE_SUPER_ADMIN") && !$entityFields->checkAnnotation($reflection, $name, "Geoks\\ApiBundle\\Annotation\\FilePath", "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {
                    $builder->add($name, $typeOptions['type'], $typeOptions['options']);
                } elseif ($name != 'roles' && !$entityFields->checkAnnotation($reflection, $name, "Geoks\\ApiBundle\\Annotation\\FilePath", "Vich\\UploaderBundle\\Mapping\\Annotation\\Uploadable")) {

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
                            'translation_domain' => $this->entityName,
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

                if ($class["type"] === 8) {
                    $typeOptions['options']['expanded'] = true;
                    $typeOptions['options']['multiple'] = true;
                    $typeOptions['options']['attr']['class'] = 'multiple';
                    $typeOptions['options']['label_attr']['class'] = 'label-multiple';

                    $builder->add($name, EntityMultipleType::class, $typeOptions['options']);

                } elseif ($class["type"] === 4 && in_array($name, $entityFields->getMultipleFields())) {

                    $typeOptions['options']['multiple'] = true;
                    $typeOptions['options']['attr']['class'] = 'multiple';
                    $typeOptions['options']['label_attr']['class'] = 'label-multiple';
                    $typeOptions['options']['query_builder'] = function (EntityRepository $er) use ($builder) {
                        return $er->createQueryBuilder('a')
                            ->where('a.' . $this->entityName  . ' IS NULL');
                    };

                    $builder
                        ->add($name, EntityMultipleType::class, $typeOptions['options'])
                        ->get($name)->addEventListener(
                            FormEvents::POST_SUBMIT,
                            function (FormEvent $event) use ($name, $entityFields, $class) {
                                $data = $event->getData();
                                $parent = $event->getForm()->getParent()->getData();

                                if ($data) {
                                    foreach ($data as $d) {
                                        $obj = $entityFields->findById($class['targetEntity'], $d);
                                        $obj->{'set' . ucfirst($this->entityName)}($parent);
                                    }
                                }
                            }
                        );
                    ;
                } else {
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
        return 'geoks_admin_create';
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

        $resolver->setRequired('entity_fields');
        $resolver->setRequired('translator');
        $resolver->setRequired('current_user');
    }
}
