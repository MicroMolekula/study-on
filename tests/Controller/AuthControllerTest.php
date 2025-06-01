<?php

namespace App\Tests\Controller;

use App\Tests\AbstractTest;

class AuthControllerTest extends AbstractTest
{
    public function testAdminLogin(): void
    {
        // тест ci
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('admin123');
        $crawler = $client->submit($form);

        //$client->clickLink('Профиль');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Профиль');
        $this->assertResponseIsSuccessful();

        $this->assertAnySelectorTextContains('div', 'admin@mail.com');
        $this->assertAnySelectorTextContains('div', 'Администратор');
    }

    public function testUserLogin(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Профиль');
        $this->assertResponseIsSuccessful();

        $this->assertAnySelectorTextContains('div', 'user@mail.com');
        $this->assertAnySelectorTextContains('div', 'Пользователь');
    }

    public function testUserLoginFailed(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();

        // При не верном пароле
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('12312332');
        $crawler = $client->submit($form);
        $this->assertAnySelectorTextContains('div.alert-danger', 'Invalid credentials.');

        // При не верном email
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user123@mail.com');
        $form['password']->setValue('zxc123412');
        $client->submit($form);
        $this->assertAnySelectorTextContains('div.alert-danger', 'Invalid credentials.');
    }

    public function testUserLogout(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient();
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $crawler = $client->submit($form);
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Выйти');
        $this->assertResponseIsSuccessful();
    }

    public function testUserAuthWithNotWorkingBilling(): void
    {
        $client = $this->client;
        $this->replaceServiceBillingClient(true);
        $client->followRedirects();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('user@mail.com');
        $form['password']->setValue('user123');
        $crawler = $client->submit($form);

        $this->assertAnySelectorTextContains('div.alert-danger', 'Сервис времменно не доступен. Попробуйте авторизоваться позднее.');
    }
}
