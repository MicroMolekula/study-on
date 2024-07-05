<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    private array $data = [
        [
            'chars_code' => 'english-language',
            'title' => 'Английский язык',
            'description' => 'Обучение основной грамматике английского языка',
        ],
        [
            'chars_code' => 'math',
            'title' => 'Математика',
            'description' => 'Обучение алгебре и геометрии, весь школьный курс',
        ],
        [
            'chars_code' => 'chinesse-language',
            'title' =>  'Китайский язык',
            'description' => 'Обучение китайского языка и культуры Китая',
        ],
        [
            'chars_code' => 'history-of-russia',
            'title' => 'История России',
            'description' => 'История России с древних времён до нашей эры, простым языком',
        ],
        [
            'chars_code' => 'physics',
            'title' => 'Физика',
            'description' => 'Обучение физики, основных понятий и теории, простым языком',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        
        foreach ($this->data as $courseData) {
            $course = new Course();
            $course->setCharsCode($courseData['chars_code'])
                ->setTitle($courseData['title'])
                ->setDescription($courseData['description']);
            $manager->persist($course);
        }
        $manager->flush();
    }
}
