<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    private array $dataUsers = [
        'admin' => [
            'username' => 'admin@mail.com',
            'password' => 'admin123',
            'roles' => [
                'ROLE_USER',
                'ROLE_SUPER_ADMIN',
            ],
            'balance' => 1000.40,
            'token' => 'admin_token',
        ],
        'user' => [
            'username' => 'user@mail.com',
            'password' => 'user123',
            'roles' => [
                'ROlE_USER',
            ],
            'balance' => 2000.30,
            'token' => 'user_token',
        ],
    ];

    public function __construct(
        private string $billingingUrl,
        private bool $ex = false,
    ) {   
    }

    public function auth(array $data, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        foreach ($this->dataUsers as $dataUser) {
            if ($data['username'] === $dataUser['username'] && $data['password'] === $dataUser['password']) {
                return ['token' => $dataUser['token']];
            }
        }
        return ['code' => 401, 'message' => 'Invalid credentials.'];
    }

    public function register(array $data, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        foreach ($this->dataUsers as $dataUser) {
            if ($dataUser['username'] === $data['username']) {
                return ['code' => 400, 'message' => 'Пользователь с таким email уже существует'];
            }
        }

        $newUser = [
            'username' => $data['username'],
            'password' => $data['password'],
            'roles' => [
                'ROLE_USER',
            ],
            'balance' => 0,
            'token' => 'new_user_token',
        ];
        $this->dataUsers['new_user'] = $newUser;
        
        return [
            'token' => $newUser['token'],
            'roles' => $newUser['roles'],
        ];

    }

    public function userCurrent(string $token, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        $user = null;

        foreach ($this->dataUsers as $dataUser) {
            if($dataUser['token'] === $token) {
                $user = $dataUser;
            }
        }

        if ($user) {
            unset($user['token'], $user['password']);
            return $user;
        }

        return ['code' => 401, 'message' => 'Invalid JWT Token'];
    }
}