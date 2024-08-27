<?php

namespace App\Form;

use App\Entity\Societe;
use App\Entity\Planning;
use App\Entity\Chauffeur;
use Doctrine\DBAL\Types\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChauffeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom',null,[
                'label'=> 'Votre nom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez votre nom'
                ]
            ])
            ->add('prenom',null,[
                'label'=> 'Votre prenom',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez votre prenom'
                ]
            ])
            ->add('dateNaissance', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de naissance',
                'required' => true,
                'constraints' => [
                   new Callback (function($dateNaissance,ExecutionContextInterface $executionContextInterface){
                    $now = new \DateTime();
                    $interval = $now->diff($dateNaissance);
                    $age = $interval->y;
                    if($age < 18){
                        $executionContextInterface->buildViolation('Vous devez avoir au moins 18 ans pour pouvoir vous inscrire')->addViolation();
                    }
                   })
                ]
            ])
            ->add('sexe',ChoiceType::class,[
                'label'=> 'Votre sexe',
                'choices' => [
                    'Masculin' => 'Masculin',
                    'Feminin' => 'Feminin',
                ],
                'attr' => [
                    'placeholder' => 'Selectionnez votre sexe'
                ]
            ])
            ->add('image')
            ->add('email')
            ->add('planning', EntityType::class, [
                'class' => Planning::class,
                'choice_label' => 'id',
            ])
            ->add('societe', EntityType::class, [
                'class' => Societe::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chauffeur::class,
        ]);
    }
}
