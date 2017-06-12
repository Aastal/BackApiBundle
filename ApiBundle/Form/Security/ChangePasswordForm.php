<?php

namespace Geoks\ApiBundle\Form\Security;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class ChangePasswordForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'label' => 'current password',
                'mapped' => false,
                'required' => false
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'current password',
                'mapped' => false,
                'required' => false
            ])
            ->add('new', RepeatedType::class, [
                'type' => 'password',
                'first_options' => array('label' => 'form.new_password'),
                'second_options' => array('label' => 'form.new_password_confirmation'),
                'invalid_message' => 'geoks.password.mismatch',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'intention'  => 'resetting',
            'csrf_protection' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'geoks_api_change_password';
    }
}
