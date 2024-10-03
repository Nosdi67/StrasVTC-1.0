<?php

namespace App\Form;

use DateTime;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
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
            ->add('nom',TextType::class,[
                'label'=> 'Votre nom'
            ])
            ->add('prenom',TextType::class,[
                'label'=> 'votre prenom'
            ])
            ->add('email',EmailType::class,[
                'label'=> 'Votre email',
                'constraints'=> [
                    new Email([
                        'message' => 'Votre email doit etre valide',
                    ])
                ]
                
            ])
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,// mapped signifie que le champ n'est pas lié à une propriété de l'entité Utilisateur, faut le gerer manuellment dans le controller 
                'type'=> PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'attr' => ['autocomplete' => 'new-password'],// indique au navigatuer que c'est un nouveau mot de passe
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/',
                        'message' => 'Le mot de passe doit contenir au moins 12 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial.'
                    ])
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
                ->add('dateNaissance',DateType::class,[
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
