<?php

namespace App\Controller;

use App\Entity\Course;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransactionController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
        private EntityManagerInterface $entityManager,
    ) {   
    }

    #[Route('/transactions', name: 'app_transaction')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $transactionsData = $this->billingClient->getTransactions($user->getApiToken());

        $transactions = [];

        foreach ($transactionsData as $transaction) {
            $transactionData = [
                'created_at' => (new \DateTimeImmutable($transaction['created_at']))->format('Y-m-d H:i'),
                'type' => $transaction['type'] === 'deposit' ? 'Пополнение' : 'Попкупка',
                'amount' => $transaction['amount'],
                'course' => isset($transaction['course_code']) ? 
                    $this->entityManager->getRepository(Course::class)->findOneBy(['chars_code' => $transaction['course_code']]) : null,
                'expires_at' => isset($transaction['expires_at']) ? 
                    (new \DateTimeImmutable($transaction['expires_at']))->format('Y-m-d H:i') : null,
            ];
            $transactions[] = $transactionData;
        }

        return $this->render('transaction/index.html.twig', [
            'transactions' => $transactions,
        ]);
    }
}
