<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class, [
            'label' => 'Nom',
            'required' => true,
            'attr' => [
                'placeholder' => 'Entrez votre nom'
            ],
            'constraints' => [
                new Regex([
                    'pattern' => '/^[a-zA-Z\s-]+$/',
                    'message' => 'Les caractères spéciaux ne sont pas autorisés dans le nom.'
                ])
            ]
        ])
        ->add('prenom', TextType::class, [
            'label' => 'Prénom',
            'required' => true,
            'attr' => [
                'placeholder' => 'Entrez votre prénom'
            ],
            'constraints' => [
                new Regex([
                    'pattern' => '/^[a-zA-Z\s-]+$/',
                    'message' => 'Les caractères spéciaux ne sont pas autorisés dans le prénom.'
                ])
            ]
        ])
        ->add('email', EmailType::class, [
            'label' => 'Votre email',
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer votre email'
                ]),
                new Regex([
                    'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                    'message' => 'Veuillez entrer une adresse email valide'
                ]),
            ],
            'attr' => [
                'placeholder' => 'Entrez votre email'
            ]
        ])
        ->add('message', TextareaType::class, [
            'label' => 'Votre message',
            'required' => true,
            'attr' => [
                'placeholder' => 'Entrez votre message'
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer votre message'
                    ])
                ],
            ])
        ->add('valider', SubmitType::class, [
            'label' => 'Envoyer',
            'attr' => [
                'class' => 'btn btn-primary'
            ]
        ]);
    }
}