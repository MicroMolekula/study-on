<?php

namespace App\Controller;

use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(BillingClient $billingClient): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $role = in_array('ROLE_SUPER_ADMIN', $user->getRoles()) ? 'Администратор' : 'Пользователь';

        $balance = $billingClient->userCurrent($user->getApiToken())['balance'];

        return $this->render('profile/index.html.twig', [
            'email' => $user->getEmail(),
            'role' => $role,
            'balance' => $balance,
        ]);
    }
}
