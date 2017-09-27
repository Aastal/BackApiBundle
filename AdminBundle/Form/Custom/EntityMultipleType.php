<?php

namespace Geoks\AdminBundle\Form\Custom;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityMultipleType extends AbstractType
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
    }

    public function getBlockPrefix()
    {
        return 'entity_multiple';
    }

    public function getParent()
    {
        return EntityType::class;
    }
}
