<?php

namespace Geoks\AdminBundle\Form\Export;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ExportType extends AbstractType
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
        $table = $options["class"];

        $banList = $container->get('geoks_admin.entity_fields')->fieldsBanList();

        $this->entityName = strtolower($container->get('geoks_admin.entity_fields')->getEntityName($table));
        $rowArr = $container->get('geoks_admin.entity_fields')->getFieldsName($table);
        $rowAssos = $container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);

        foreach ($rowArr as $name => $field) {
            if ($field["type"] != 'array' && !in_array($name, $banList)) {
                $typeOptions = $container->get('geoks_admin.entity_fields')->switchType($this->entityName, $name, $field["type"]);

                $builder
                    ->add($name, $typeOptions['type'], $typeOptions['options']);
            }
        }

        foreach ($rowAssos as $name => $class) {

            if ($class['isOwningSide']) {
                $builder
                    ->add($name, EntityType::class, [
                        'label' => ucfirst($name),
                        'class' => $class['targetEntity'],
                        'empty_value' => "",
                        'attr' => [
                            'class' => 'control-animate'
                        ]
                    ]);
            }
        }

        $builder
            ->add('export', SubmitType::class, [
                'label' => "Exporter",
                'attr' => [
                    'class' => 'btn btn-info'
                ]
            ]);
    }

    public function getBlockPrefix()
    {
        return 'geoks_filter';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            "required" => false
        ));

        $resolver->setRequired('class');
        $resolver->setRequired('service_container');
    }
}