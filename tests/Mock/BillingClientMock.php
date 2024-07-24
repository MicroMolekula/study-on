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
            'token' => '',
            'refresh_token' => 'admin',
        ],
        'user' => [
            'username' => 'user@mail.com',
            'password' => 'user123',
            'roles' => [
                'ROlE_USER',
            ],
            'balance' => 2000.30,
            'token' => '',
            'refresh_token' => 'user',
        ],
    ];

    private array $dataCourses = [
        [
            'code' => 'math',
            'type' => 'buy',
            'price' => 2000.5,
        ],
        [
            'code' => 'english-language',
            'type' => 'rent',
            'price' => 1000.3,
        ],
        [
            'code' => 'chinesse-language',
            'type' => 'rent',
            'price' => 1500.3,
        ],
        [
            'code' => 'history-of-russia',
            'type' => 'free',
        ],
        [
            'code' => 'physics',
            'type' => 'buy', 
            'price' => 1900.2
        ],
    ];

    private int $transactionsId = 6;

    private array $dataTransactions = [
        'user@mail.com' => [
            [
                'id' => 0,
                'type' => 'deposit',
                'amount' => 3000,
                'created_at' => '2024-07-13T13:46:07+00:00',
            ],
            [
                'id' => 1,
                'course_code' => 'english-language',
                'type' => 'payment', 
                'amount' => 1000.50,
                'created_at' => '2024-07-13T14:01:37+00:00',
                'expires_at' => '2024-07-20T14:01:37+00:00',
            ],
            [
                'id' => 2,
                'course_code' => 'physics',
                'type' => 'payment',
                'amount' => 1900.20,
                'created_at' => '2024-07-14T11:01:20+00:00',
            ],
            [
                'id' => 3,
                'type' => 'deposit',
                'amount' => 2099.30,
                'created_at' => '2024-07-14T11:01:20+00:00',
            ],
        ],
        'admin@mail.com' => [
            [
                'id' => 4,
                'type' => 'deposit',
                'amount' => 6000,
                'created_at' => '2024-07-15T12:46:07+00:00',
            ],
            [
                'id' => 5,
                'course_code' => 'math',
                'type' => 'payment',
                'amount' => 2000.50,
                'created_at' => '2024-07-15T13:46:07+00:00',
            ],
            [
                'id' => 6,
                'type' => 'deposit',
                'amount' => 5999.50,
                'created_at' => '2024-07-15T16:46:07+00:00',
            ],
        ],
    ];

    public function __construct(
        private string $billingingUrl,
        private bool $ex = false,
    ) {   
        $this->dataUsers['admin']['token'] = $_ENV['ADMIN_TOKEN'];
        $this->dataUsers['user']['token'] = $_ENV['USER_TOKEN'];
    }

    public function auth(array $data, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        foreach ($this->dataUsers as $dataUser) {
            if ($data['username'] === $dataUser['username'] && $data['password'] === $dataUser['password']) {
                return [
                    'token' => $dataUser['token'],
                    'refresh_token' => $dataUser['refresh_token'],
                ];
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
            'refresh_token' => explode('@', $data['username'])[0],
        ];
        $newUser['token'] = $_ENV['NEW_USER_TOKEN'];
        $this->dataUsers['new_user'] = $newUser;
        
        return [
            'token' => $newUser['token'],
            'roles' => $newUser['roles'],
            'refresh_token' => $newUser['refresh_token'],
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
            unset($user['token'], $user['password'], $user['refresh_token']);
            return $user;
        }

        return ['code' => 401, 'message' => 'Invalid JWT Token'];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'token' => $this->dataUsers[$refreshToken]['token'],
        ];   
    }

    public function getAllCourses(): array
    {
        return $this->dataCourses;
    }

    public function getCourse(string $code): array
    {
        $courses = array_filter($this->dataCourses, fn($var) => $var['code'] === $code);
        if (empty($courses)) {
            return [
                'error_code' => 404,
                'message' => 'Курс не найден'
            ];
        }
        return end($courses);
    }

    public function getTransactions(string $token, string $type = '', string $courseCode = '', bool $skipExpired = false): array
    {
        $user = array_filter($this->dataUsers, fn($var) => $var['token'] === $token);
        $transactions = $this->dataTransactions[end($user)['username']];
        if ($type !== '') {
            if ($type !== 'deposit' || $type !== 'payment') {
                return [
                    'code' => 400,
                    'message' => "Тип \"$type\" не существует",
                ];
            }
            $transactions = array_filter($transactions, fn($var) => $var['type'] === $type);
        }
        if ($courseCode !== '') {
            $courses = array_filter($this->dataCourses, fn($var) => $var['code'] === $courseCode);
            if (empty($courses)) {
                return [
                    'code' => 400,
                    'message' => "Курс $courseCode не существует",
                ];
            }
            $transactions = array_filter($transactions, fn($var) => isset($var['course_code']) && $var['course_code'] === $courseCode);
        }
        if ($skipExpired) {
            $transactions = array_filter($transactions, fn($var) => !isset($var['expires_at']));
        }
        return $transactions;
    }

    public function payCourse(string $token, string $courseCode): array
    {
        $courses = array_filter($this->dataCourses, fn($var) => $var['code'] === $courseCode);
        if (empty($courses)) {
            return [
                'code' => 400,
                'message' => "Курс $courseCode не найден",
            ];
        }
        $course = end($courses);

        $users = array_filter($this->dataUsers, fn($var) => $var['token'] === $token);
        $user = end($users);

        if ($course['price'] > $user['balance']) {
            return [
                'code' => 406,
                'message' => 'На вашем счету не достаточно средств',
            ];
        }

        $this->transactionsId += 1;
        $this->dataUsers[explode('@', $user['username'])[0]]['balance'] -= $course['price'];
        $this->dataTransactions[] = [
            'id' => $this->transactionsId,
            'created_at' => new \DateTimeImmutable(),
            'type' => 'payment',
            'course_code' => $courseCode,
            'amount' => $course['price'],
            'expires_at' => $course['type'] === 'rent' ? (new \DateTimeImmutable())->modify('+7 day') : null,
        ];

        return [
            'message' => 'Курс куплен',
            'course_code' => $courseCode,
            'amount' => $course['price'],
        ];
    }
}