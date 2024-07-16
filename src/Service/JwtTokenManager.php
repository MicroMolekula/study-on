<?php

namespace App\Service;

class JwtTokenManager
{
    // В минутах
    private int $reserveTimeCheck = 5;

    private function decode(string $token): array
    {
        $jwtArr = array_combine(['header', 'payload', 'hash'], explode('.', $token));
        $payload = json_decode(base64_decode($jwtArr['payload']), true);
        return $payload;
    }

    public function isExpired(string $token): mixed
    {
        $payload = $this->decode($token);

        $exp = (int) $payload['exp'];
        $dateTimeNow = (new \DateTime())->getTimestamp();
        $timestampReserve = $this->reserveTimeCheck * 60;
        $timeNowWithReserve = $dateTimeNow + $timestampReserve;

        return $exp < $timeNowWithReserve ? true : false;
    }
}