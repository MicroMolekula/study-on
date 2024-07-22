<?php

namespace App\Service;

use App\Entity\Course;
use Symfony\Bundle\SecurityBundle\Security;

class PurchaseControl
{
    public function __construct(
        private Security $security,
        private BillingClient $billingClient,
    ) {
    }

    public function getDataCourse(Course $course): array
    {
        $billingResponse = $this->billingClient->getCourse($course->getCharsCode());

        $courseData = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'lessons' => $course->getLessons(),
            'type' => isset($billingResponse['type']) ? $billingResponse['type'] : 'free',
            'price' => isset($billingResponse['price']) ? $billingResponse['price'] : null,
        ];

        $checkPurchased = $this->checkPurchasedCourse($course->getCharsCode(), $courseData['type']);

        $courseData = array_merge($courseData, $checkPurchased);

        return $courseData;
    }

    public function checkPurchasedCourse(string $courseCode, string $courseType): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return [
                'isPurchased' => false,
            ];
        }

        $transactions = $this->billingClient->getTransactions($user->getApiToken(), courseCode: $courseCode);
        if (empty($transactions)) {
            return [
                'isPurchased' => false,
            ];
        }

        if ($courseType === 'rent') {
            $transaction = end($transactions);
            $expiresAt = new \DateTimeImmutable($transaction['expires_at']);
            if ($expiresAt->getTimestamp() < (new \DateTimeImmutable('now'))->getTimestamp()) {
                return [
                    'isPurchased' => false,
                ];
            }
            return [
                'isPurchased' => true,
                'message' => "Арендовано до {$expiresAt->format('Y-m-d H:i')}",
            ];
        }

        return [
            'isPurchased' => true,
            'message' => 'Куплено',
        ];
    }
}