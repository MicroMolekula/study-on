<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private CourseFixtures $courseFixtures,
        private LessonFixtures $lessonFixtures,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->courseFixtures->load($manager);
        $this->lessonFixtures->load($manager);
    }
}
