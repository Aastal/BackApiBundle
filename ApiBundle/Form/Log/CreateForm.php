<?php

namespace Geoks\ApiBundle\Form\Log;

use Geoks\ApiBundle\Entity\Log;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'control-animate'
                ]
            ])
            ->add('page', TextType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'control-animate'
                ]
            ])
            ->add('details', TextType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'control-animate'
                ]
            ])
        ;

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function(FormEvent $event) {
                $form = $event->getForm();

                /** @var Log $log */
                $log = $event->getData();

                $context = [
                    $form->get('page')->getData() => $form->get('details')->getData()
                ];

                $log->setContext($context);
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => "Geoks\ApiBundle\Entity\Log",
            'csrf_protection' => false
        ));
    }

    public function getName()
    {
        return '';
    }
}
