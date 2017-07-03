<?php

namespace Geoks\AdminBundle\Form\Import;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ImportType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ->add('csv', FileType::class, [
                'label' => "Importer un fichier CSV",
                'attr' => [
                    'class' => 'control-animate'
                ]
            ])
            ->add('images', FileType::class, [
                'label' => "Images liées à l'import",
                'required' => false,
                'multiple' => true,
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
            'data_class' => 'Geoks\AdminBundle\Entity\Import',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            "required" => true
        ));

        $resolver->setRequired('class');
    }
}
