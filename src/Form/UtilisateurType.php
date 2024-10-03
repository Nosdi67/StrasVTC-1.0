<?php
namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File; 
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex; 
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('roles')
            ->add('password', RepeatedType::class, [
                'mapped' => false,
                'required' => true,
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
                        'max' => 4096,
                    ]),
                ],
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password']
            ])
            ->add('nom', TextType::class, [
                'label'=> 'Votre nom',
                'required'=> true
            ])
            ->add('prenom', TextType::class, [
                'label'=> 'Votre prénom',
                'required'=> true
            ])
            ->add('sexe', ChoiceType::class, [
                'choices'=> [
                    'Masculin' => 'Masculin',
                    'Féminin' => 'Féminin'
                ],
                'label'=> 'Votre sexe',
                'required'=> true
            ])
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'required'=> true
            ])
            ->add('photo', FileType::class, [
                'label'=> 'Votre photo',
                'required'=> false,
                'data_class' => null,
                'constraints' => [
                    new File([
                        'maxSize' => '10024k', // 10024k = 10Mo
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Ce format d\'image n\'est pas supporté',
                    ])
                ],
            ])
            ->add('isVerified')

            ->add('valider', SubmitType::class,[
                'label'=> 'Valider',
                'attr' => [
                    'class' => 'btn '
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
