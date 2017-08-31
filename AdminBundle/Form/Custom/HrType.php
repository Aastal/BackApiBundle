<?php

namespace Geoks\AdminBundle\Form\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HrType extends AbstractType
{
    /**
     * @var array
     */
    private $hr;

    public function __construct(array $hr)
    {
        $this->hr = $hr;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'hr' => $this->hr,
        ));
    }

    public function getName()
    {
        return 'hr';
    }
}
