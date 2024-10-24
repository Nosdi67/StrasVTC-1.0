<?php

namespace App\Form;

use App\Entity\Societe;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SocieteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la société',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse de la société',
            ])
            ->add('telephone', NumberType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => [
                    'min' => 0
                ],
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 10,
                        'minMessage' => 'Le numéro de téléphone doit contenir au moins {{ limit }} chiffres',
                        'maxMessage' => 'Le numéro de téléphone doit contenir au maximum {{ limit }} chiffres',
                    ])
                ]
            ])
            ->add('email', TextType::class, [
                'label' => 'Email de la société',
                'attr' => [
                    'placeholder' => 'exemple@exemple.com'
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                        'message' => 'L\'adresse email n\'est pas valide',
                    ])
                ]
            ])  
            ->add('codePostal',TextType::class,[
                'label' => 'Code postal de la société',
                'constraints' => [
                    new Length([
                        'min' => 5,
                        'max' => 5,
                        'minMessage' => 'Le code postal doit contenir au moins {{ limit }} chiffres',
                        'maxMessage' => 'Le code postal doit contenir au maximum {{ limit }} chiffres',
                    ])
                ]
            ])
            ->add('ville', TextType::class,[
                'label' => 'Ville de la société'
            ])
            ->add('pays',TextType::class,[
                'label' => 'Pays de la société'
            ])
            ->add('image', FileType::class, [
                'label' => 'Image de la société',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4194304', //4 mb
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/jpg',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Ce format d\'image n\'est pas supporté',
                    ])
                ]
            ])
            ->add('valider', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'btn'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Societe::class,
        ]);
    }
}
