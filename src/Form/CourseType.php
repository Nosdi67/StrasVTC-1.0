<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Utilisateur;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDepart', null, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
            ])
            ->add('adresseDepart',null,[
                'label' => 'Adresse de départ',
                
            ])
            ->add('adresseArivee',null,[
                'label' => 'Adresse d\'arrivée',
                
            ])
            ->add('prix',null,[
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
            ->add('devis')
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            // ->add('valider', SubmitType::class, [
            //     'label' => 'Valider',
            //     'attr' => ['id' => 'findChauffeurLink'],
            // ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
