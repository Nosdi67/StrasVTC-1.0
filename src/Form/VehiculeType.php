<?php

namespace App\Form;

use App\Entity\Chauffeur;
use App\Entity\Vehicule;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marque',TextType::class,[
                'label'=> 'Marque du véhicule',
                'attr' => [
                    'placeholder' => 'Entrez le marque du véhicule'
                ]
            ])
            ->add('modele',TextType::class,[
                'label'=> 'Modele du véhicule',
                'attr' => [
                    'placeholder' => 'Entrez la modele du véhicule'
                ]
            ])
            ->add('categorie',ChoiceType::class,[
                'label'=> 'Catégorie du véhicule',
                'placeholder' => 'Choissisez la catégorie du véhicule',
                'choices' => [
                    'Berline' => 'Berline',
                    'Van' => 'Van'
                    ]
            ])
            ->add('nbPlace',ChoiceType::class,[
                'label'=> 'Nombre de place',
                'placeholder' => 'Choissisez le nombre de place',
                'choices' => [
                   '1' => 1,
                   '2' => 2,
                   '3' => 3,
                   '4' => 4,
                   '5' => 5,
                   '6' => 6,
                   '7' => 7,
                ]
            ])
            ->add('image',FileType::class,[
                'label' => 'Image du véhicule',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Choissisez une image'
                ]
            ])
            ->add('chauffeur', EntityType::class, [
                'class' => Chauffeur::class,
                'choice_label' => 'id',
            ])
            ->add('valider',SubmitType::class,[
                'attr' => ['class' => 'btn vehicule-form-submit']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}
