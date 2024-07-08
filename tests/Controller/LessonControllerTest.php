<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\LessonFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;

class LessonControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [
            CourseFixtures::class,
            LessonFixtures::class,
        ];
    }

    // Проверка вывода уроков на странице курса
    public function testLessonIndex(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $this->assertResponseIsSuccessful();

        // Проверка правильного вывода количества уроков для одного курса
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $this->assertEquals(
            count($manager->getRepository(Lesson::class)->findBy(['course' => $course])),
            $crawler->filter('ol > li')->count()
        );
    }

    // Проверка вывода информации урока
    public function testLessonShow(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $lessonTest = $course->getLessons()[0];
        $crawler = $client->clickLink($lessonTest->getTitle());
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', $lessonTest->getTitle());
        $this->assertAnySelectorTextContains('a', $lessonTest->getCourse()->getTitle());
        $this->assertSelectorTextContains('div.card', $lessonTest->getContent());

        $client->request('GET', '/lessons/111111111');
        $this->assertResponseStatusCodeSame(404);
    }

    // Проврка формы на добавление нового урока с пустыми полями ввода
    public function testLessonNewWithEmptyFields(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $client->clickLink('Пройти');
        $crawler = $client->clickLink('Добавить урок');
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[title]']->setValue('');
        $form['lesson[content]']->setValue('');
        $form['lesson[ordering]']->setValue('');

        $client->submit($form);

        $this->assertSelectorTextContains('.invalid-feedback', 'Заполните это поле');
        $this->assertResponseIsUnprocessable();
    }

    // Проверка формы на добавление нового урока с полем порядка больше 10000
    public function testLessonNewWithBadOrdering(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $client->clickLink('Пройти');
        $crawler = $client->clickLink('Добавить урок');
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[title]']->setValue('Present Perfect');
        $form['lesson[content]']->setValue('Выучим новое время в английском языке');
        $form['lesson[ordering]']->setValue(1000000);

        $client->submit($form);

        $this->assertSelectorTextContains('.invalid-feedback', 'Порядковый номер урока должен быть меньше или равен 10000');
        $this->assertResponseIsUnprocessable();
    }

    // Проверка на добавление нового урока
    public function testLessonNew(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $crawler = $client->clickLink('Добавить урок');
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Сохранить')->form();
        $form['lesson[title]']->setValue('Present Perfect');
        $form['lesson[content]']->setValue('Выучим новое время в английском языке');
        $form['lesson[ordering]']->setValue(100);

        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            count($manager->getRepository(Lesson::class)->findBy(['course' => $course])),
            $crawler->filter('ol > li')->count()
        );
        $lesson = $manager->getRepository(Lesson::class)->findOneBy(['title' => 'Present Perfect']);
        $this->assertNotNull($lesson);
        $this->assertEquals(
            $lesson->getContent(),
            'Выучим новое время в английском языке',
        );
        $this->assertEquals(
            $lesson->getOrdering(),
            100,
        );
    }

    // Проверка формы на изменнение уже существующего урока
    public function testLessonEdit(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $lessonTest = $course->getLessons()[0];
        $client->clickLink($lessonTest->getTitle());
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Редактировать');
        
        $form = $crawler->selectButton('Изменить')->form();
        $formValues = $form->getValues();
        
        $this->assertEquals($lessonTest->getTitle(), $formValues['lesson[title]']);
        $this->assertEquals($lessonTest->getContent(), $formValues['lesson[content]']);
        $this->assertEquals($lessonTest->getOrdering(), $formValues['lesson[ordering]']);

        $form['lesson[title]']->setValue('Present Perfect');
        $form['lesson[content]']->setValue('Выучим новое время в английском языке');
        $form['lesson[ordering]']->setValue(100);
        $client->submit($form);

        $manager->clear();
        $this->assertResponseIsSuccessful();
        $lesson = $manager->getRepository(Lesson::class)->findOneBy(['title' => 'Present Perfect']);
        $this->assertNotNull($lesson);
        $this->assertEquals(
            $lesson->getContent(),
            'Выучим новое время в английском языке',
        );
        $this->assertEquals(
            $lesson->getOrdering(),
            100,
        );
    }

    // Проверка формы на уделение урока
    public function testLessonDelete(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $lessonTest = $course->getLessons()[0];
        $crawler = $client->clickLink($lessonTest->getTitle());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $lesson = $manager->getRepository(Lesson::class)->findOneBy(['title' => $lessonTest->getTitle()]);
        $this->assertNull($lesson);
    }
}
