<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\LessonFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use Symfony\Component\Mime\Message;

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
        $client = $this->client;
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
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('admin123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('admin123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('admin123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('admin123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('admin123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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

    public function testLessonShowFailed(): void
    {
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $this->replaceServiceBillingClient();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $lesson = $manager->getRepository(Lesson::class)->findOneBy(['course' => $course]);
        $client->clickLink($lesson->getTitle());
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextNotContains('h1', $lesson->getTitle());
        $this->assertAnySelectorTextContains('h1', 'Войдите в свой аккаунт');
    }

    public function testLessonNewFailed(): void
    {
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $this->replaceServiceBillingClient();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');
        
        // Проверка есть ли не у авторизованного пользователя кнопка добавить урок
        $this->assertSelectorTextNotContains('a', 'Добавить урок');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $crawler = $client->submit($form);
        $crawler = $client->clickLink('Пройти');
        $this->assertResponseIsSuccessful();

        // Проверка есть ли у обычного авторизованного пользователя кнопка добавить урок
        $this->assertSelectorTextNotContains('a', 'Добавить урок');
    }

    public function testLessonEditDeleteFailed(): void
    {
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $client->followRedirects();
        $this->replaceServiceBillingClient();
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Пройти');

        $course = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $lesson = $manager->getRepository(Lesson::class)->findOneBy(['course' => $course]);
        $client->clickLink($lesson->getTitle());

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextNotContains('a', 'Редактировать');
        $this->assertAnySelectorTextNotContains('a', 'Удалить');
        $this->assertAnySelectorTextContains('h1', 'Войдите в свой аккаунт');

        // Авторизация
        $client->request('GET', '/courses');
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextNotContains('a', 'Редактировать');
        $this->assertAnySelectorTextNotContains('a', 'Удалить');
    }
}
