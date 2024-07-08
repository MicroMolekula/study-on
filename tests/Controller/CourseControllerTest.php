<?php

namespace App\Test\Controller;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\LessonFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;

class CourseControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [
            CourseFixtures::class,
            LessonFixtures::class,
        ];
    }

    public function testCourseIndex(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            $manager->getRepository(Course::class)->count([]),
            $crawler->filter('div.card')->count()
        );
    }

    public function testCourseShow(): void
    {

        $client = static::getClient();
        $client->followRedirects();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();
        
        // Проверка правильного вывода количества уроков для одного курса
        // $crawler = $client->clickLink('Пройти');
        // $this->assertResponseIsSuccessful();
        // $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        // $this->assertEquals(
        //     count($manager->getRepository(Lesson::class)->findBy(['course' => $course])),
        //     $crawler->filter('ol > li')->count()
        // );

        // Проверка правильного вывода количества уроков на всех страницах курсов
        foreach ($crawler->filter('div.card  a') as $link) {
            $crawler = $client->request('GET', $link->attributes['href']->value);
            $this->assertResponseIsSuccessful();
            $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
            $this->assertEquals(
                count($manager->getRepository(Lesson::class)->findBy(['course' => $course])),
                $crawler->filter('ol > li')->count()
            );
        }

        // Проверка статуса по прохождению по не существующему курсу
        $client->request('GET', '/courses/1111111');
        $this->assertResponseStatusCodeSame(404);
    }

    // Проверка формы добавления нового курса при вводе пустых значений полей
    public function testCourseNewWithEmptyFields(): void
    {
        $client = static::getClient();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Новый курс');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[chars_code]']->setValue('');
        $form['course[title]']->setValue('');
        $form['course[description]']->setValue('');
        $client->submit($form);
        $this->assertSelectorTextContains('.invalid-feedback', 'Заполните это поле');
        $this->assertResponseIsUnprocessable();
    }

    // Проверка формы добавления нового курса при вводе уже существующего кода курса
    public function testCourseNewWithNotUniqueCode(): void
    {
        $client = static::getClient();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Новый курс');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[chars_code]']->setValue('english-language');
        $form['course[title]']->setValue('Английский язык 2');
        $form['course[description]']->setValue('');
        $client->submit($form);
        $this->assertSelectorTextContains('.invalid-feedback', 'Символьный код должен быть уникальным');
        $this->assertResponseIsUnprocessable();
    }

    // Проверка формы добавления нового курса с валидными данными
    public function testCourseNew(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Новый курс');
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Сохранить')->form();
        $form['course[chars_code]']->setValue('programming');
        $form['course[title]']->setValue('Программирование');
        $form['course[description]']->setValue('Программирование для всех');
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            $manager->getRepository(Course::class)->count([]),
            $crawler->filter('div.card')->count()
        );
        $course = $manager->getRepository(Course::class)->findOneBy(['chars_code' => 'programming']);
        $this->assertNotEquals($course, null);
        $this->assertEquals($course->getTitle(), 'Программирование');
        $this->assertEquals($course->getDescription(), 'Программирование для всех');
    }

    // Провекра изменения курса
    public function testCourseEdit(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses/');
        $crawler = $client->clickLink('Пройти');
        $courseTitle = $crawler->filter('h1')->text();
        $crawler = $client->clickLink('Редактировать');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Изменить')->form();
        $formValues = $form->getValues();
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $courseTitle]);
        $this->assertEquals($course->getTitle(), $formValues['course[title]']);
        $this->assertEquals($course->getCharsCode(), $formValues['course[chars_code]']);
        $this->assertEquals($course->getDescription(), $formValues['course[description]']);

        $form['course[chars_code]']->setValue('programming');
        $form['course[title]']->setValue('Программирование');
        $form['course[description]']->setValue('Программирование для всех');
        $crawler = $client->submit($form);

        $manager->clear();
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            $manager->getRepository(Course::class)->count([]),
            $crawler->filter('div.card')->count()
        );
        $courseEdited = $manager->getRepository(Course::class)->findOneBy(['chars_code' => 'programming']);
        $this->assertNotNull($courseEdited);
        $this->assertEquals($courseEdited->getTitle(), 'Программирование');
        $this->assertEquals($courseEdited->getDescription(), 'Программирование для всех');
    }

    // Проверка удаления курса
    public function testCourseDelete(): void
    {
        $client = static::getClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $courseTitle = $crawler->filter('h1')->text();
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Удалить')->form();
        $crawler = $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            $manager->getRepository(Course::class)->count([]),
            $crawler->filter('div.card')->count()
        );
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $courseTitle]);
        $this->assertNull($course);
    }
}
