<?php

namespace App\Controller;

use App\Service\BillingClient;
use App\Service\JwtTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(BillingClient $billingClient): Response
    {
        $user = $this->getUser();
        dd($user);
        return $this->render('test/index.html.twig', [
            'token' => '',
        ]);
    }
}
