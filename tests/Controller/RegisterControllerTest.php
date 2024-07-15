<?php

namespace App\Tests\Controller;

use App\Tests\AbstractTest;

class RegisterControllerTest extends AbstractTest
{
    public function testRegister(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['register[email]']->setValue('new_user@mail.com');
        $form['register[password]']->setValue('123456');
        $form['register[confirmPassword]']->setValue('123456');

        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterWithNotUniqueEmail(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('user@mail.com');
        $form['register[password]']->setValue('123456');
        $form['register[confirmPassword]']->setValue('123456');
        $client->submit($form);

        $this->assertAnySelectorTextContains('div.alert-danger', 'Пользователь с таким email уже существует');
    }

    public function testRegisterWithNotValidData(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        // При некорректном email
        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('new_user@mail');
        $form['register[password]']->setValue('123456');
        $form['register[confirmPassword]']->setValue('123456');
        $crawler = $client->submit($form);
        $this->assertAnySelectorTextContains('div.invalid-feedback', 'Некорректный email');

        // При некорректном подтверждении пароля
        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('new_user@mail.com');
        $form['register[password]']->setValue('122342');
        $form['register[confirmPassword]']->setValue('122341234');
        $crawler = $client->submit($form);
        $this->assertAnySelectorTextContains('div.invalid-feedback', 'Пароли не совпадают');

        // При пустом email
        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('');
        $form['register[password]']->setValue('2222222');
        $form['register[confirmPassword]']->setValue('2222222');
        $crawler = $client->submit($form);
        $this->assertAnySelectorTextContains('div.invalid-feedback', 'Введите email');

        // При пустом пароле
        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('new_user@mail.com');
        $form['register[password]']->setValue('');
        $form['register[confirmPassword]']->setValue('');
        $crawler = $client->submit($form);
        $this->assertAnySelectorTextContains('div.invalid-feedback', 'Введите пароль');

        // При пустом подтверждении пароля
        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('new_user@mail.com');
        $form['register[password]']->setValue('123456');
        $form['register[confirmPassword]']->setValue('');
        $crawler = $client->submit($form);
        $this->assertAnySelectorTextContains('div.invalid-feedback', 'Подтвердите пароль');
    }

    public function testRegisterWithNotWorkingBilling(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient(true);
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Зарегистрироваться');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]']->setValue('new_user22@mail.com');
        $form['register[password]']->setValue('123456');
        $form['register[confirmPassword]']->setValue('123456');
        $crawler = $client->submit($form);

        $this->assertAnySelectorTextContains('div.alert-danger', 'Сервис временно недоступен. Попробуйте зарегистироваться позже.');
    }
}
