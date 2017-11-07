<?php

namespace Geoks\AdminBundle\Form\Basic;

use Doctrine\ORM\EntityRepository;
use Geoks\AdminBundle\Services\EntityFields;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Geoks\AdminBundle\Form\Custom\HrType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Geoks\AdminBundle\Form\Custom\EntityExpandedType;
use Geoks\AdminBundle\Form\Custom\EntityMultipleType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UpdateForm extends AbstractType
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var boolean
     */
    private $changePassword;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->changePassword = $options["change_password"];

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

        $passwordExist = false;

        if (isset($rowArr["password"]) || isset($rowArr["plainPassword"])) {
            $passwordExist = true;
        }

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
                if ($annotation = $reader->getPropertyAnnotation($reflectionProperty, "Vich\\UploaderBundle\\Mapping\\Annotation\\UploadableField") && !in_array($reflectionProperty->name, $banList)) {
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

                if ($class["type"] === 8 && in_array($name, $entityFields->getMultipleFields()) && !$class['mappedBy']) {
                    $typeOptions['options']['expanded'] = true;
                    $typeOptions['options']['multiple'] = true;
                    $typeOptions['options']['attr']['class'] = 'multiple';
                    $typeOptions['options']['label_attr']['class'] = 'label-multiple';

                    $builder->add($name, EntityExpandedType::class, $typeOptions['options']);

                } elseif ($class["type"] === 4 && in_array($name, $entityFields->getMultipleFields()) && $class['mappedBy']) {

                    $typeOptions['options']['multiple'] = true;
                    $typeOptions['options']['attr']['class'] = 'multiple';
                    $typeOptions['options']['label_attr']['class'] = 'label-multiple';
                    $typeOptions['options']['query_builder'] = function (EntityRepository $er) use ($builder, $class) {
                        return $er->createQueryBuilder('a')
                            ->where('a.' . $class['mappedBy']  . ' IS NULL OR a.' . $class['mappedBy'] . ' = ' . $builder->getData()->getId());
                    };

                    $builder
                        ->add($name, EntityMultipleType::class, $typeOptions['options'])
                        ->get($name)->addEventListener(
                            FormEvents::PRE_SUBMIT,
                            function (FormEvent $event) use ($name, $entityFields, $class) {
                                $data = $event->getData();
                                $parent = $event->getForm()->getParent()->getData();

                                if (!$data) {
                                    foreach ($parent->{'get' . ucfirst($name)}()->toArray() as $d) {
                                        $obj = $entityFields->findById($class['targetEntity'], $d);
                                        $obj->{'set' . ucfirst($this->entityName)}(null);
                                    }
                                } else {
                                    $diff = array_diff($data, $parent->{'get' . ucfirst($name)}()->toArray());

                                    if ($diff) {
                                        foreach ($data as $d) {

                                            $obj = $entityFields->findById($class['targetEntity'], $d);

                                            if (in_array($d, $data)) {
                                                $obj->{'set' . ucfirst($this->entityName)}($parent);
                                            } else {
                                                $obj->{'set' . ucfirst($this->entityName)}(null);
                                            }
                                        }
                                    }
                                }
                            }
                        )
                    ;
                } elseif ($class["type"] === 2) {
                    $builder->add($name, EntityType::class, $typeOptions['options']);

                    $typeOptions['options']['query_builder'] = function (EntityRepository $er) use ($builder, $class) {
                        return $er->createQueryBuilder('a')
                            ->where('a.' . $class['mappedBy'] . ' != ' . $builder->getData()->getId());
                    };
                } elseif ($class["type"] === 1 && $class['inversedBy'] !== null) {
                    $builder->add($name, EntityType::class, $typeOptions['options']);
                }
            }
        }

        if ($passwordExist == true) {
            $builder
                ->add('changePassword', ButtonType::class, [
                    'label' => "user.changePassword",
                    'attr' => [
                        'class' => 'btn btn-animate btn-light-default'
                    ]
                ])
            ;
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();

                if ($this->changePassword) {
                    $form
                        ->remove('changePassword')
                        ->add('password_title', HrType::class, [
                            'label'    => 'user.changePassword',
                            'mapped'   => false,
                            'required' => false,
                            'attr' => [
                                'class' => 'form_title'
                            ]
                        ])
                        ->add('plainPassword', PasswordType::class, [
                            'label' => 'user.password',
                            'attr' => [
                                'class' => 'control-animate'
                            ]
                        ])
                        ->add('passwordConfirm', PasswordType::class, [
                            'label' => 'user.confirmPassword',
                            'required' => true,
                            'mapped' => false,
                            'attr' => [
                                'class' => 'control-animate'
                            ]
                        ])
                    ;
                }
            }
        );
    }

    public function getBlockPrefix()
    {
        return 'geoks_admin_update';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'change_password' => false
        ));

        $resolver->setRequired('entity_fields');
        $resolver->setRequired('translator');
        $resolver->setRequired('current_user');
    }
}
