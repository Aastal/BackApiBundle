<?php

namespace Geoks\AdminBundle\Form\Custom;

use Geoks\AdminBundle\Services\CountriesPhone;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('phone', TextType::class, [
                'attr' => [
                    'placeholder' => 'form.phone',
                    'class' => 'form-control phone',
                    'maxlength' => 13
                ]
            ])
            ->add('dialCode', HiddenType::class, [
                'mapped' => false,
                'data' => "+33",
                'attr' => [
                    'class' => 'dialCode'
                ]
            ])
        ;

        $builder
            ->addEventListener(
                FormEvents::SUBMIT,
                function(FormEvent $event) {
                    $form = $event->getForm();

                    $concatData = $form->get('dialCode')->getData() . $form->get('phone')->getData();
                    $phoneUtil = PhoneNumberUtil::getInstance();

                    $phoneNumber = $phoneUtil->parse($concatData, PhoneNumberFormat::INTERNATIONAL);
                    $event->setData($phoneNumber);
                }
            )
        ;
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
            'compound' => true,
            'max_length' => 13,
            'countries_phone' => $this->countriesPhone
        ));
    }

    public function getBlockPrefix()
    {
        return 'phone';
    }
}