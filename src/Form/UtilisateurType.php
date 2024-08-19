<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',null,[
                'label'=> 'Votre email'
            ])
            ->add('roles')
            ->add('password',RepeatedType::class,[
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
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'required' => true,
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],  
            ])
            ->add('nom',null,[
                'label'=> 'Votre nom',
                'required'=> true
            ])
            ->add('prenom',null,[
                'label'=> 'votre prenom',
                'required'=> true
            ])
            ->add('sexe',ChoiceType::class,[
                'choices'=> [
                    'Masculin'=>'Masculin',
                    'Feminin'=>'Feminin'
                ],
                'label'=> 'votre sexe',
                'required'=> true
            ])
            ->add('dateNaissance', null, [
                'widget' => 'single_text',
                'required'=> true
            ])
            ->add('photo')
            ->add('isVerified')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
