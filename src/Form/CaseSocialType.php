<?php
// src/Form/CaseSocialType.php

namespace App\Form;

use App\Entity\CaseSocial;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaseSocialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('images', FileType::class, [
                'label' => 'Images (optional)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => ['accept' => 'image/*'],
            ])
            ->add('cha9a9aLink', TextType::class)
            ->add('isUrgent', CheckboxType::class, ['required' => false])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CaseSocial::class,
        ]);
    }
}
