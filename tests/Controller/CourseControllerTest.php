<?php

namespace App\Test\Controller;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\LessonFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Service\PurchaseControl;
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
        $client = $this->client;
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

        $client = $this->client;
        $client->followRedirects();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        // Проверка есть ли не авторизованного пользователя кнопка покупки курса
        $crawler = $client->clickLink('Пройти');
        $this->assertAnySelectorTextNotContains('button', 'Арендовать');
        $this->assertAnySelectorTextNotContains('button', 'Купить');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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

    public function testCoursePay(): void
    {
        $client = $this->client;
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $purchaseControl = static::getContainer()->get(PurchaseControl::class);
        $client->followRedirects();
        $client->request('GET', '/courses/');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Пройти');
        $course = $entityManager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()]);
        $courseData = $purchaseControl->getDataCourse($course);

        if ($courseData['type'] === 'rent') {
            $crawler = $client->clickLink('Арендовать');
        } else if ($courseData['type'] === 'buy') {
            $crawler = $client->clickLink('Купить');
        } else {
            $this->assertResponseIsSuccessful();
            return;
        }
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('div.alert-success', 'Курс куплен');

        // Проверка доступна ли кнопка покупки при не достаточных средствах на счету
        $course = $entityManager->getRepository(Course::class)->findOneBy(['title' => 'Физика']);
        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseIsSuccessful();
        $this->assertTrue($crawler->selectButton('Купить')->getNode(0)->hasAttribute('disabled'));

        
        // Проверка покупки курса при недостаточных средствах на счету
        $course = $entityManager->getRepository(Course::class)->findOneBy(['title' => 'Физика']);
        $crawler = $client->request('GET', '/courses/' . $course->getId() . '/pay');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('div.alert', 'На вашем счету не достаточно средств');
    }

    // Проверка формы добавления нового курса при вводе пустых значений полей
    public function testCourseNewWithEmptyFields(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses/');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Пройти');
        $this->assertResponseIsSuccessful();
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
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();

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

    public function testCourseNewFailed(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        // Проверка, есть ли не у авторизованного пользователя кнопка "Новый курс"
        $this->assertAnySelectorTextNotContains('a', 'Новый курс');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();

        // Проверка, есть ли у обычного авторизованного пользователя кнопка "Новый курс"
        $this->assertAnySelectorTextNotContains('a', 'Новый курс');

        // Проверка может ли обычный авторизованный пользователь перейти на страницу создания нового курса
        $client->request('GET', '/courses/new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseEditDeleteFailed(): void
    {
        $client = $this->client;
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Пройти');

        // Проверка есть ли не у авторизованного пользователя кнопка "Редактировать"
        $this->assertAnySelectorTextNotContains('a', 'Редактировать');
        $this->assertAnySelectorTextNotContains('a', 'Удалить');

        // Авторизация
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Пройти');

        // Проверка есть ли у обычного авторизованного пользователя кнопка "Редактировать"
        $this->assertAnySelectorTextNotContains('a', 'Редактировать');
        $this->assertAnySelectorTextNotContains('a', 'Удалить');

        $courseId = $manager->getRepository(Course::class)->findOneBy(['title' => $crawler->filter('h1')->text()])->getId();
        // Проверка может ли обычный авторизованный пользователь перейти на страницу редактирования курса
        $client->request('GET', '/courses/' . $courseId. '/edit');
        $this->assertResponseStatusCodeSame(403);
    }
}
