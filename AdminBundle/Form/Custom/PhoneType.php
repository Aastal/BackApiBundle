<?php

namespace Geoks\AdminBundle\Form\Custom;

use Geoks\AdminBundle\Services\CountriesPhone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhoneType extends AbstractType
{

    /**
     * @var CountriesPhone
     */
    protected $countriesPhone;

    public function __construct(CountriesPhone $countriesPhone)
    {
        $this->countriesPhone = $countriesPhone->getPhones();
    }

    /**
     * Pass the countries list
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['countries_phone'] = $this->countriesPhone;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'max_length' => 13,
            'countries_phone' => $this->countriesPhone
        ));
    }

    public function getBlockPrefix()
    {
        return 'phone';
    }
}