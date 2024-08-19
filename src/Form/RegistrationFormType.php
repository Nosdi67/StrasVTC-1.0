<?php

namespace App\Form;

use DateTime;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photo',FileType::class,[
                'label'=> 'Votre photo de profile',
                'mapped' => false,
                'required' => false
            ])
            ->add('nom',null,[
                'label'=> 'Votre nom'
            ])
            ->add('prenom',null,[
                'label'=> 'votre prenom'
            ])
            ->add('email')
            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'type'=> PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'required' => true,
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                ])
                ->add('sexe', ChoiceType::class, [
                    'choices' => [
                        'Masculin' => 'Masculin',
                        'Feminin' => 'Feminin',
                    ],
                    'label' => 'Sexe',
                    'required' => true,
                ])
                ->add('dateNaissance',DateTimeType::class,[
                    'label'=> 'Date de naissance',
                    'widget' => 'single_text',
                    'required' => true,
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez saisir une date de naissance',
                        ]),
                        new Callback(function ($dateNaissance, ExecutionContextInterface $executionContextInterface) {
                            // on verifie si l'utilisateur est majeur
                            $now = new DateTime();
                            $interval = $now->diff($dateNaissance);
                            $age = $interval->y;
                            if ($age < 18) {
                                $executionContextInterface->addViolation('Vous devez être majeur pour vous inscrire');
                            }
                        })
                    ]
                ])
                ->add('agreeTerms', CheckboxType::class, [
                    'mapped' => false,
                    'constraints' => [
                        new IsTrue([
                            'message' => 'You should agree to our terms.',
                        ]),
                    ],
                ])
                ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
