<?php

namespace Geoks\AdminBundle\Form\Import;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportImageType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('images', FileType::class, [
                'label' => "Importer des images",
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
        return 'geoks_image_import';
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
