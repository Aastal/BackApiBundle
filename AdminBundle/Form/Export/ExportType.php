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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var array
     */
    private $rowArr;

    /**
     * @var array
     */
    private $rowAssos;

    /**
     * ExportType constructor.
     *
     * @param ContainerInterface $container
     * @param string $table
     */
    public function __construct(ContainerInterface $container, $table)
    {
        $this->table = $table;
        $this->container = $container;

        $this->entityName = lcfirst($this->container->get('geoks_admin.entity_fields')->getEntityName($table));
        $this->rowArr = $this->container->get('geoks_admin.entity_fields')->getFieldsName($table);
        $this->rowAssos = $this->container->get('geoks_admin.entity_fields')->getFieldsAssociations($table);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->rowArr as $name => $field) {
            if ($field["type"] != 'array') {
                $typeOptions = $this->container->get('geoks_admin.entity_fields')->switchType($this->entityName, $name, $field["type"]);

                $builder
                    ->add($name, $typeOptions['type'], $typeOptions['options']);
            }
        }

        foreach ($this->rowAssos as $name => $class) {

            if ($class['isOwningSide']) {
                $builder
                    ->add($name, EntityType::class, [
                        'label' => ucfirst($name),
                        'class' => $class['targetEntity'],
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
            'data_class' => $this->table,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            "required" => false
        ));
    }
}