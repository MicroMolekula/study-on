<?php

namespace App\Controller;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use App\Service\JwtTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        $billingClient = new BillingClientMock('');
        dd($_ENV['ADMIN_TOKEN']);
        return $this->render('test/index.html.twig', [
            'token' => '',
        ]);
    }
}
