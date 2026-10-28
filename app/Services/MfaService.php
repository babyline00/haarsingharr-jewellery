<?php

namespace App\Services;

use Illuminate\Support\Str;

class MfaService
{
    public function newSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $account): string
    {
        $issuer = config('app.name', 'Haarsingharr');
        $label = rawurlencode($issuer.':'.$account);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function verifyCode(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        try {
            $key = $this->base32Decode($secret);
        } catch (\InvalidArgumentException) {
            return false;
        }
        $step = intdiv($timestamp ?? time(), 30);
        for ($window = -1; $window <= 1; $window++) {
            $counter = $step + $window;
            if ($counter < 0) {
                continue;
            }
            $message = pack('N2', 0, $counter);
            $hash = hash_hmac('sha1', $message, $key, true);
            $offset = ord($hash[19]) & 0x0f;
            $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;
            if (hash_equals(sprintf('%06d', $binary % 1000000), $code)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function recoveryCodes(int $count = 10): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(4).'-'.Str::random(4)))
            ->all();
    }

    private function base32Encode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($value) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
        }

        return $encoded;
    }

    private function base32Decode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Z2-7]/i', '', $value))) as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                throw new \InvalidArgumentException('Invalid authenticator secret.');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}
