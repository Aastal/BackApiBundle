<?php

namespace Geoks\AdminBundle\Form\Basic;

use Doctrine\ORM\EntityManager;
use Geoks\AdminBundle\Form\Custom\HrType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

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
        $container = $options["service_container"];
        $table = $options["data_class"];

        $banList = $container->get('geoks_admin.entity_fields')->fieldsBanList();

        $this->entityName = strtolower($container->get('geoks_admin.entity_fields')->getEntityName($table));
        $rowArr = $container->get('geoks_admin.entity_fields')->getFieldsName($table);
        $rowAssos = $container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);

        $passwordExist = false;

        if (isset($rowArr["password"]) || isset($rowArr["plainPassword"])) {
            $passwordExist = true;
        }

        foreach ($rowArr as $name => $field) {
            if (($field["type"] != 'array' && $field["type"]) && !in_array($name, $banList)) {

                $typeOptions = $container->get('geoks_admin.entity_fields')->switchType($this->entityName, $name, $field["type"]);

                if ((isset($field["nullable"]) && $field["nullable"]) || $field["type"] == 'boolean') {
                    $typeOptions['options']['required'] = false;
                } else {
                    $typeOptions['options']['required'] = true;
                }

                $builder->add($name, $typeOptions['type'], $typeOptions['options']);
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
                }

                $builder
                    ->add($name, EntityType::class, $typeOptions['options']);
            }
        }

        if ($passwordExist == true) {
            $builder
                ->add('changePassword', ButtonType::class, [
                    'label' => "user.changePassword",
                    'attr' => [
                        'class' => 'btn btn-animate btn-light-blue'
                    ]
                ]);
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
        return '';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ));

        $resolver->setRequired('change_password');
        $resolver->setRequired('service_container');
    }
}