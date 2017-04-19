<?php

namespace Geoks\AdminBundle\Form\Import;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportType extends AbstractType
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
        $table = $options["class"];

        $builder
            ->add('type', ChoiceType::class, [
                'label' => "Type d'import",
                'choices' => [
                    'incremental' => "Incrémentale",
                    'replace' => "Annule & Remplace"
                ],
                'attr' => [
                    'class' => 'control-animate'
                ]
            ])
            ->add('import_csv', FileType::class, [
                'label' => "Importer un fichier CSV",
                'attr' => [
                    'class' => 'control-animate'
                ]
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'geoks_import';
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
            "required" => true
        ));

        $resolver->setRequired('class');
    }
}