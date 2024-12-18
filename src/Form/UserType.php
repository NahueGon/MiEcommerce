<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
class UserType extends AbstractType
{
    private const INPUT_STYLE = "form-control";
    private const LABEL_STYLE = "form-label";

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'label' => false,
            'required' => false,
            'label_attr' => [
                'class' => self::LABEL_STYLE
            ],
            'attr' => [
                'placeholder' => 'Nombre'
            ]
        ])
        ->add('lastname', TextType::class, [
            'label' => false,
            'required' => false,
            'label_attr' => [
                'class' => self::LABEL_STYLE
            ],
            'attr' => [
                'placeholder' => 'Apellido'
            ]
        ])
        ->add('email', EmailType::class, [
            'label' => false,
            'required' => false,
            'label_attr' => [
                'class' => self::LABEL_STYLE
            ],
            'attr' => [
                'placeholder' => 'Email'
            ]
        ])
        ->add('img_profile', FileType::class, [
            'label' => false,
            'required' => false,
            'data_class' => null,
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'maxSizeMessage' => 'Es demasiado pesada la imagen',
                    'mimeTypes' => [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/jfif',
                    ],
                    'mimeTypesMessage' => 'Por favor sube un formato valido de imagen',
                ])
            ],
        ]);

        if (!$options['is_edit']) {
            $builder
            ->add('password', PasswordType::class, [
                'label' => false,
                'required' => $options['is_edit'] ? true : false,
                'label_attr' => [
                    'class' => self::LABEL_STYLE
                ],
                'attr' => [
                    'placeholder' => 'Contraseña'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Registrarme',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
        }else{
            $builder
            ->add('gender', ChoiceType::class, [
                'label' => false,
                'required' => $options['is_edit'] ? true : false,
                'label_attr' => [
                    'class' => self::LABEL_STYLE
                ],
                'choices' => [
                    'Hombre' => 'male',
                    'Mujer' => 'female',
                    'Otro' => 'other'
                ],
            ])
            ->add('old_password', PasswordType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'label_attr' => [
                    'class' => self::LABEL_STYLE
                ],
                'attr' => [
                    'placeholder' => 'Contraseña Actual'
                ],
            ])
            ->add('new_password', PasswordType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'label_attr' => [
                    'class' => self::LABEL_STYLE
                ],
                'attr' => [
                    'placeholder' => 'Contraseña Nueva'
                ],
                'constraints' => [
                    new Assert\Length([
                        'min' => 4,
                        'minMessage' => 'La contraseña debe tener al menos 4 caracteres',
                    ]),
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Guardar Cambios',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
        }
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
