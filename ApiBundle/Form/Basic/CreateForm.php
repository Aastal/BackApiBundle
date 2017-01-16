<?php

namespace Geoks\ApiBundle\Form\Basic;

use Doctrine\ORM\EntityManager;
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

        $banList = $container->get('geoks_admin.entity_fields')->fieldsBanList();

        $this->entityName = strtolower($container->get('geoks_admin.entity_fields')->getEntityName($table));
        $rowArr = $container->get('geoks_admin.entity_fields')->getFieldsName($table);
        $rowAssos = $container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);

        foreach ($rowArr as $name => $field) {
            if ($field["type"] != 'array' && !in_array($name, $banList)) {
                $typeOptions = $container->get('geoks_admin.entity_fields')->switchType($this->entityName, $name, $field["type"]);

                if ((isset($field["nullable"]) && $field["nullable"]) && $field["type"] != 'datetime' || $field["type"] == 'boolean') {
                    $typeOptions['options']['required'] = false;
                } else {
                    $typeOptions['options']['required'] = true;
                }

                $builder->add($name, $typeOptions['type'], $typeOptions['options']);
            }
        }

        foreach ($rowAssos as $name => $class) {

            if ($class['isOwningSide']) {
                $builder
                    ->add($name, EntityType::class, [
                        'label' => $this->entityName . "." . $name,
                        'class' => $class['targetEntity'],
                        'attr' => [
                            'class' => 'control-animate'
                        ]
                    ]);
            }
        }

        if ($this->entityName == "user") {
            $builder
                ->add('plainPassword', PasswordType::class, [
                    'label' => 'user.password',
                    'attr' => [
                        'class' => 'control-animate'
                    ]
                ]);
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