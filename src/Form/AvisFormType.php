<?php

namespace App\Form;

use DateTime;
use App\Entity\Avis;
use App\Entity\Chauffeur;
use App\Entity\Utilisateur;
use Dompdf\FrameDecorator\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class AvisFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note',NumberType::class,[
                'attr' => [
                    'min' => 0,
                    'max' => 5,
                ],
                'label' => 'Note',
            ])
            ->add('text', TextType::class, [
                'attr' => [
                    'minlength' => 10,
                    'maxlength' => 255,
                ],
                'label' => 'Commentaire',
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 255,
                        'minMessage' => 'Le commentaire doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'invalid_message' => 'La longueur du commentaire n\'est pas valide.',
                'error_bubbling' => true,
            ])
            ->add('dateAvis', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('chauffeur', EntityType::class, [
                'class' => Chauffeur::class,
                'choice_label' => 'id',
            ])
            ->add('valider',SubmitType::class,[
                'attr' => [
                    'class' => 'btn',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}
