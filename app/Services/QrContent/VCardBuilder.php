<?php

declare(strict_types=1);

namespace App\Services\QrContent;

final class VCardBuilder
{
    /**
     * Generate a vCard 4.0 string from the provided fields.
     *
     * @param  array<string, string|null>  $fields  Non-sensitive fields from settings JSON
     * @param  string|null  $phone  Decrypted phone number
     * @param  string|null  $email  Decrypted email address
     */
    public function build(array $fields, ?string $phone, ?string $email): string
    {
        $firstName = $this->escape($fields['vcard_first_name'] ?? '');
        $lastName = $this->escape($fields['vcard_last_name'] ?? '');
        $company = $this->escape($fields['vcard_company'] ?? '');
        $jobTitle = $this->escape($fields['vcard_job_title'] ?? '');
        $website = $fields['vcard_website'] ?? '';
        $address = $this->escape($fields['vcard_address'] ?? '');
        $photoUrl = $fields['vcard_photo_url'] ?? '';

        $fullName = trim("{$firstName} {$lastName}");

        $lines = [
            'BEGIN:VCARD',
            'VERSION:4.0',
        ];

        if ($fullName !== '') {
            $lines[] = "FN:{$fullName}";
            $lines[] = "N:{$lastName};{$firstName};;;";
        }

        if ($company !== '') {
            $lines[] = "ORG:{$company}";
        }

        if ($jobTitle !== '') {
            $lines[] = "TITLE:{$jobTitle}";
        }

        if ($phone !== null && $phone !== '') {
            $lines[] = 'TEL;TYPE=WORK:'.$this->escape($phone);
        }

        if ($email !== null && $email !== '') {
            $lines[] = 'EMAIL;TYPE=WORK:'.$this->escape($email);
        }

        if ($website !== '') {
            $lines[] = "URL:{$website}";
        }

        if ($address !== '') {
            $lines[] = "ADR;TYPE=WORK:;;{$address};;;;";
        }

        if ($photoUrl !== '') {
            $lines[] = "PHOTO;VALUE=URI:{$photoUrl}";
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines);
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', '\\', "\n"], ['\\,', '\\;', '\\\\', '\\n'], $value);
    }
}
