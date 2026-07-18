<?php

namespace Botble\EWallet\Services;

class GiftCardCodeGenerator
{
    protected string $prefix;

    protected int $segmentLength = 4;

    protected int $segmentCount = 3;

    public function __construct()
    {
        $this->prefix = get_gift_card_code_prefix();
    }

    public function generate(): string
    {
        $segments = [];

        for ($i = 0; $i < $this->segmentCount; $i++) {
            $segments[] = $this->generateSegment();
        }

        $codeWithoutChecksum = $this->prefix . '-' . implode('-', $segments);
        $checksum = $this->calculateLuhnCheckDigit($codeWithoutChecksum);

        $lastIndex = $this->segmentCount - 1;
        $segments[$lastIndex] .= $checksum;

        return $this->prefix . '-' . implode('-', $segments);
    }

    protected function generateSegment(): string
    {
        $characters = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $segment = '';

        for ($i = 0; $i < $this->segmentLength; $i++) {
            $randomIndex = random_int(0, strlen($characters) - 1);
            $segment .= $characters[$randomIndex];
        }

        return $segment;
    }

    public function isValid(string $code): bool
    {
        $pattern = '/^[A-Z]{2,4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{5}$/';

        if (! preg_match($pattern, strtoupper($code))) {
            return false;
        }

        return $this->validateLuhn($code);
    }

    protected function calculateLuhnCheckDigit(string $code): string
    {
        $numeric = $this->alphaToNumeric($code);
        $sum = 0;
        $alt = false;

        for ($i = strlen($numeric) - 1; $i >= 0; $i--) {
            $n = (int) $numeric[$i];

            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }

            $sum += $n;
            $alt = ! $alt;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (string) $checkDigit;
    }

    protected function validateLuhn(string $code): bool
    {
        $cleanCode = preg_replace('/[^A-Z0-9]/i', '', $code);
        $codeWithoutCheck = substr($cleanCode, 0, -1);
        $providedCheck = substr($cleanCode, -1);

        $formatted = $this->reformatForChecksum($codeWithoutCheck);
        $expectedCheck = $this->calculateLuhnCheckDigit($formatted);

        return $providedCheck === $expectedCheck;
    }

    protected function alphaToNumeric(string $code): string
    {
        $code = preg_replace('/[^A-Z0-9]/i', '', strtoupper($code));
        $result = '';

        foreach (str_split($code) as $char) {
            if (ctype_alpha($char)) {
                $result .= ord($char) - ord('A') + 10;
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    protected function reformatForChecksum(string $cleanCode): string
    {
        $prefixLength = strlen($this->prefix);
        $prefix = substr($cleanCode, 0, $prefixLength);
        $rest = substr($cleanCode, $prefixLength);
        $segments = str_split($rest, $this->segmentLength);

        return $prefix . '-' . implode('-', $segments);
    }
}
