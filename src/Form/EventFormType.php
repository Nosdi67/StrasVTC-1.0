<?php

namespace App\Form;

use App\Entity\Chauffeur;
use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
->add('titre', TextType::class,[
    'label' => 'Le titre de l\'événement'
])
->add('debut', DateTimeType::class, [
    'widget' => 'single_text',
    'label' => 'Date de début'
])
->add('fin', DateTimeType::class, [
    'widget' => 'single_text',
    'label' => 'Date de fin'
])
->add('journee',null,[
    'label' => 'Bloquer la journée',
    'required' => false
])

->add('chauffeur', EntityType::class, [
    'class' => Chauffeur::class,
    'choice_label' => 'nom',  
    'label' => 'Le chauffeur'
])
->add('valider', SubmitType::class,[
    'attr' => ['class' => 'btn']
])
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
