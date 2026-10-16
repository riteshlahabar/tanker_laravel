<?php

declare(strict_types=1);

namespace App\Modules\Notification\Support;

/**
 * Turns a stored mobile number into the fully qualified form WhatsApp wants.
 *
 * **Why this is done here and not left to the Node service.** Its own
 * `normalizePhoneToJid` prefixes the country code only when the number does
 * not already begin with those digits — so an Indian ten-digit mobile that
 * happens to start `91` (`9123456789` is a perfectly ordinary number) is left
 * as it is and the message goes to somebody else entirely. Sending a number
 * that is already qualified means that guess never runs.
 */
final readonly class PhoneNumber
{
    public const COUNTRY_CODE = '91';

    /**
     * The national number length this normaliser knows about.
     */
    private const NATIONAL_DIGITS = 10;

    /**
     * @return string the number as digits only, country code included, or an
     *                empty string when there is nothing usable to send to
     */
    public static function qualify(?string $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw) ?? '';

        // Written 0XXXXXXXXXX for trunk dialling, or 0091… for international.
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === self::NATIONAL_DIGITS) {
            return self::COUNTRY_CODE.$digits;
        }

        // Already qualified — for India that is 12 digits. Anything longer is
        // another country's number and is passed through untouched.
        return $digits;
    }

    public static function isSendable(?string $raw): bool
    {
        return strlen(self::qualify($raw)) >= self::NATIONAL_DIGITS;
    }
}
