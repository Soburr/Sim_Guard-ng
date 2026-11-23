<?php

namespace App\Support;

class NigerianPhone
{
    public static function normalize(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            $subscriber = substr($digits, 3);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $subscriber = substr($digits, 1);
        } elseif (strlen($digits) === 10 && in_array($digits[0], ['7', '8', '9'], true)) {
            $subscriber = $digits;
        } else {
            return null;
        }

        if (! preg_match('/^(70|80|81|90|91)\d{8}$/', $subscriber)) {
            return null;
        }

        return '+234' . $subscriber;
    }

    public static function detectTelco(string $e164): string
    {
        $prefix = '0' . substr($e164, 4, 3); // e.g., 0803
        $extPrefix = '0' . substr($e164, 4, 4); // e.g., 07025

        if (in_array($extPrefix, ['07025', '07026', '0704'], true)) {
            return 'mtn';
        }

        return match ($prefix) {
            '0803', '0806', '0703', '0706', '0813', '0816', '0810', '0814', '0903', '0906', '0913', '0916' => 'mtn',
            '0802', '0808', '0708', '0812', '0701', '0902', '0901', '0904', '0907', '0912', '0911' => 'airtel',
            '0805', '0807', '0705', '0815', '0811', '0905', '0915' => 'glo',
            '0809', '0818', '0817', '0909', '0908' => '9mobile',
            default => 'mtn',
        };
    }
}