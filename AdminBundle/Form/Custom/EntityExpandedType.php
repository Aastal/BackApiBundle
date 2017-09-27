<?php

namespace Geoks\AdminBundle\Form\Custom;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityExpandedType extends AbstractType
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
        return 'entity_expanded';
    }

    public function getParent()
    {
        return EntityType::class;
    }
}
