<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chars_code', TextType::class,[
                'label' => 'Код курса',
            ])
            ->add('title', TextType::class, [
                'label' => 'Название курса',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание курса'
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
