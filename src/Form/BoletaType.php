<?php

namespace App\Form;

use App\Entity\Boleta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            ->add('profesional', TextType::class, [
                'label'       => 'Carátula',
                'constraints' => [
                    new NotBlank(['message' => 'La carátula es obligatoria.']),
                    new Length(['max' => 255]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('fechaVencimiento', DateType::class, [
                'label'    => 'Vencimiento',
                'required' => false,
                'widget'   => 'single_text',
                'html5'    => true,
                'attr'     => ['class' => 'form-control']
            ])
            ->add('archivoOriginal', FileType::class, [
                'label' => 'PDF / imagen de la boleta',
                'mapped' => false,
                'required' => (bool) $options['require_original_file'],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Subí un PDF o una imagen válida (JPG, PNG o WEBP).',
                    ]),
                ],
                'help' => 'Se guarda localmente en el sistema y queda disponible para ver/descargar.',
            ])
        ;

        if (!empty($options['show_comprobante_upload'])) {
            $builder->add('comprobantePago', FileType::class, [
                'label' => 'Comprobante de pago',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Subí un PDF o una imagen válida (JPG, PNG o WEBP).',
                    ]),
                ],
                'help' => 'Al subirlo, la boleta puede marcarse como pagada.',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Boleta::class,
            'require_original_file' => true,
            'show_comprobante_upload' => false,
        ]);

        $resolver->setAllowedTypes('require_original_file', 'bool');
        $resolver->setAllowedTypes('show_comprobante_upload', 'bool');
    }
}