<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class BillingClient
{
    public function __construct(
        private string $billingUrl,
    ) {
    }

    private function request(
        string $method = 'GET',
        string $url = null,
        array $data = [],
        array $headers = [],
        string $token = '',
    ): array
    {
        $headers[] = 'Authorization:Bearer ' . $token;
        $headers[] = 'Content-type:application/json';
        $curlOptions = [
            CURLOPT_URL => $this->billingUrl . $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method == 'POST') {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        $curlHandler = curl_init();
        curl_setopt_array($curlHandler, $curlOptions);
        $response = curl_exec($curlHandler);

        if(curl_errno($curlHandler)){
            throw new BillingUnavailableException('Сервис времменно не доступен');
        }

        curl_close($curlHandler);
        return json_decode($response, true);
    }
    
    public function auth(array $data): array
    {
        return $this->request(
            method: 'POST',
            url: '/api/v1/auth',
            data: $data,
        );
    }

    public function register(array $data): array
    {
        return $this->request(
            method: 'POST',
            url: '/api/v1/register',
            data: $data
        );
    }

    public function userCurrent(string $token): array
    {
        return $this->request(
            url: '/api/v1/users/current',
            token: $token,
        );
    }

}