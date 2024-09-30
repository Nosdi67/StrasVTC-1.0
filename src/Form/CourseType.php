<?php

namespace App\Form;

use DateTime;
use App\Entity\Course;
use App\Entity\Utilisateur;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\GreaterThan;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new DateTime('tomorrow'))->format('Y-m-d\TH:i'),
                ],
                'constraints' => [
                    new GreaterThan([
                        'value' => (new DateTime())->modify('+1 day'),
                        'message' => 'La date de départ doit être supérieure à la date du jour.',
                    ])
                ]
            ])
            ->add('adresseDepart',TextType::class,[
                'label' => 'Adresse de départ',
                
            ])
            ->add('adresseArivee',TextType::class,[
                'label' => 'Adresse d\'arrivée',
                
            ])
            ->add('prix',IntegerType::class,[
                'label' => 'Prix',
                
            ])
            ->add('vehicule', ChoiceType::class, [
                'label' => 'Véhicule',
                'choices' => [
                    'Choisir le véhicule' => '',
                    'Van' => 'Van',
                    'Berline' => 'Berline',
                ],
                'placeholder' => 'Choisir le véhicule',
            ])
            ->add('nbPassager',ChoiceType::class,[
                'label' => 'Nombre de passager',
                'placeholder' => 'Choisir le nombre de passager',
                'choices' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                ],
            ])
            ->add('devis',null,[
                'required' => false,
                'label' => 'Devis',
            ])
            ->add('prix',NumberType::class,[
                'label' => 'Prix',
            ])
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => function ($utilisateur) {
                    return $utilisateur->getNom() . ' ' . $utilisateur->getPrenom();
                },
            ])
            ->add('chauffeur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'nom',
            ])
            ->add('valider', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['id' => 'findChauffeurLink'],
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
