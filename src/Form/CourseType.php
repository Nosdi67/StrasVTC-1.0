<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Utilisateur;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('nbPassager')
            ->add('devis')
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('Valider', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'btn btn-primary'],
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
