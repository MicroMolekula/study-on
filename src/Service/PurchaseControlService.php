<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;

class PurchaseControlService
{
    public function __construct(
        private Security $security,
        private BillingClient $billingClient,
    ) {
    }

    public function checkPurchasedCourse(string $courseCode): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return [
                'isPurhased' => false,
            ];
        }

        $transactions = $this->billingClient->getTransactions($user->getApiToken(), courseCode: $courseCode);
        if (empty($transactions)) {
            return [
                'isPurchased' => false,
            ];
        }

        return [];
    }
}