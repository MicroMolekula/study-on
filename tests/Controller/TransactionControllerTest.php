<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\LessonFixtures;
use App\Tests\AbstractTest;

class TransactionControllerTest extends AbstractTest
{

    protected function getFixtures(): array
    {
        return [
            CourseFixtures::class,
            LessonFixtures::class,
        ];
    }

    public function testTransactionHistoryIndex(): void
    {
        $client = $this->client;
        $client->followRedirects();
        $this->replaceServiceBillingClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('Войти');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.com';
        $form['password'] = 'admin123';
        $client->submit($form);
        $this->assertResponseIsSuccessful();
        
        $client->clickLink('Профиль');
        $this->assertResponseIsSuccessful();

        $crawler = $client->clickLink('История транзакций');
        $this->assertResponseIsSuccessful();
    }
}
