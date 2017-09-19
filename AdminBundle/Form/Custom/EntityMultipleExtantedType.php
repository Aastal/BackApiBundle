<?php

namespace Geoks\AdminBundle\Form\Custom;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityMultipleExtantedType extends EntityType
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
        return 'entity_multiple_extanted';
    }
}
