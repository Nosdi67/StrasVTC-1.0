<?php

namespace App\Form;

use App\Entity\Societe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocieteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom',TextType::class,[
                'label' => 'Nom de la société',
            ])
            ->add('adresse',TextType::class,[
                'label' => 'Adresse de la société',
            ])
            ->add('telephone',NumberType::class,[
                'label' => 'Numéro de téléphone',
            ])
            ->add('email',TextType::class,[
                'label' => 'Email de la société',
            ])  
            ->add('image',FileType::class,[
                'label'=> 'Image de la société',
                'mapped' => false
            ])
            ->add('valider',SubmitType::class,[
                'label' => 'Valider',
                'attr' => [
                    'class' => 'btn'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Societe::class,
        ]);
    }
}
