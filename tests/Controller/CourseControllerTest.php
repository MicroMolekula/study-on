<?php

namespace App\Test\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Component\DomCrawler\Crawler;

class CourseControllerTest extends WebTestCase
{
    public function testGetIndex(): void
    {
        $client = static::createClient();
        $manager = self::getContainer()->get('doctrine')->getManager();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            $manager->getRepository(Course::class)->count([]),
            $crawler->filter('div.card')->count()        
        );
    }

    public function testGetShow(): void
    {
        $client = static::createClient();
        $client->followRedirects();
        $manager = self::getContainer()->get('doctrine')->getManager();
        $crawler = $client->request('GET', '/courses');
        $links = [];
        foreach ($crawler->filter('div.card > a') as $el) {
            $links[] = (new Crawler($el))->link();
        }

        $countLessonsDB = [];
        $countLessonsPages = [];

        foreach ($links as $link) {
            $crawler = $client->request('GET', $link->getUri());
            $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);

            $countLessonsDB[] = count($manager->getRepository(Lesson::class)->findBy(['course' => $course]));
            $countLessonsPages[] = $crawler->filter('ol > li')->count();
        }
        
        $this->assertEquals($countLessonsDB, $countLessonsPages);
    }
}
