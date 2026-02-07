<?php
namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['required' => false, 'attr' => ['class' => 'form-control', 'rows' => 4]])
            ->add('startAt', DateTimeType::class, ['widget' => 'single_text', 'attr' => ['class' => 'form-control']])
            ->add('image', FileType::class, [
                'label' => 'Event image (optional)',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*']
            ])
            ->add('location', TextType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Evenement::class]);
    }
}
