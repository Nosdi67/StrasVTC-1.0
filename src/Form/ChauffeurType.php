<?php

namespace App\Form;

use App\Entity\Societe;
use App\Entity\Chauffeur;
use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChauffeurType extends AbstractType
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
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de Naissance',
                'required' => true,
                'constraints' => [
                    new Callback(function ($dateNaissance, ExecutionContextInterface $executionContextInterface) {
                        $now = new \DateTime();
                        $interval = $now->diff($dateNaissance);
                        $age = $interval->y;
                        if ($age < 18) {
                            $executionContextInterface->buildViolation('Vous devez avoir au moins 18 ans pour pouvoir vous inscrire')
                                                      ->addViolation();
                        }
                    })
                ]
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Masculin' => 'Masculin',
                    'Féminin' => 'Féminin',
                ],
                'attr' => [
                    'placeholder' => 'Sélectionnez votre sexe'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10024k', // 10024k = 10Mo
                        'mimeTypes' => [ // Liste des formats d'images supportés
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Ce format d\'image n\'est pas supporté',
                    ]),
                ],
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre email'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // les caractères autorisés dans l'adresse email
                        'message' => 'Veuillez entrer une adresse email valide'
                    ]),
                    ],
                'attr' => [
                    'placeholder' => 'Entrez votre email'
                ]
            ])
            // ->add('evenement', EntityType::class, [
            //     'class' => Evenement::class,
            //     'choice_label' => 'id',
            //     'required' => false
            // ])
            ->add('societe', EntityType::class, [
                'class' => Societe::class,
                'choice_label' => 'nom',
                'label' => 'Société',
            ])
            ->add('valider', SubmitType::class, [
                'label'=> 'Valider',
                'attr' => [
                    'class' => 'btn'
                ]
                ]);
        }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chauffeur::class,
        ]);
    }
}
