<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    public function auth(array $data): array
    {
        switch ($data['username']) {
            case 'admin@mail.com':
                return ['token' => 'admin_token'];
            case 'user@mail.com':
                return ['token' => 'user_token'];
            case 'new_user@mail.com':
                return ['token' => 'new_user_token'];
            default:
                return ['message' => 'Invalid credentials.'];
        }
    }

    public function userCurrent(string $token): array
    {
        return [];
    }
}