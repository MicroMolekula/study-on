<?php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(BillingClient $billingClient): Response
    {
        $token = $billingClient->auth([
            'username' => 'krasikov@gmail.com',
            'password' => 'zxc12345',
        ])['token'];
        $data = $billingClient->userCurrent($token);
        dd($data);
        return $this->render('test/index.html.twig', [
            'token' => '',
        ]);
    }
}
