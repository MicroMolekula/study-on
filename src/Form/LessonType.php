<?php

namespace App\Form;

use App\Entity\Lesson;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\DataTransformer\CourseToNumberTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function __construct(
        private CourseToNumberTransformer $transformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название урока',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Содержимое урока',
            ])
            ->add('ordering', IntegerType::class, [
                'label' => 'Порядковый номер урока',
            ])
            ->add('course', HiddenType::class)
        ;

        $builder->get('course')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
