<?php

namespace App\Form;

use App\Dto\CourseDto;
use App\Entity\Course;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('code', TextType::class,[
                'label' => 'Код курса',
            ])
            ->add('title', TextType::class, [
                'label' => 'Название курса',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Бесплатный' => 'free',
                    'Аренда' => 'rent',
                    'Платный' => 'buy',
                ],
                'label' => 'Тип курса'
            ])
            ->add('price', MoneyType::class, [
                'help' => 'Если курс бесплатный, то введенная цена не будет учитываться',
                'label' => 'Цена курса',
                'scale' => 2,
                'currency' => 'RUB',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание курса'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseDto::class,
        ]);
    }
}
