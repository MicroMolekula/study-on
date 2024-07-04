<?php
namespace App\Form\DataTransformer;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToNumberTransformer implements DataTransformerInterface
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function reverseTransform(mixed $id): ?Course
    {
        
        if (!$id) {
            return null;
        }

        $course = $this->manager->getRepository(Course::class)->findOneBy(['id' => $id]);

        if (null === $course) {
            throw new TransformationFailedException('Курса по id - "%s" не сущесвует', $id);
        }

        return $course;
    }

    public function transform(mixed $course): string
    {
        if (null === $course) {
            return '';
        }

        return $course->getId();
    }
}