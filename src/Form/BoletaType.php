<?php

namespace App\Form;

use App\Entity\Boleta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class BoletaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroBoleta', TextType::class, [
                'label'       => 'N° Boleta',
                'constraints' => [
                    new NotBlank(['message' => 'El número de boleta es obligatorio.']),
                    new Length(['max' => 100]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('expediente', TextType::class, [
                'label'    => 'Expediente',
                'required' => false,
                'attr'     => ['class' => 'form-control']
            ])
            ->add('profesional', TextType::class, [
                'label'       => 'Profesional',
                'constraints' => [
                    new NotBlank(['message' => 'El profesional es obligatorio.']),
                    new Length(['max' => 50]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('fechaVencimiento', DateType::class, [
                'label'    => 'Fecha vencimiento',
                'required' => false,
                'widget'   => 'single_text',
                'html5'    => true,
                'attr'     => ['class' => 'form-control']
            ])
            ->add('emailProfesional', EmailType::class, [
                'label'    => 'Email profesional',
                'constraints' => [
                    new Length(['max' => 50]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('observaciones', TextareaType::class, [
                'label'    => 'Observaciones',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Notas, aclaraciones…',
                    'class' => 'form-control'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Boleta::class,
        ]);
    }
}