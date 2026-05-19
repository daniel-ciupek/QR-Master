<?php

declare(strict_types=1);

namespace App\Services\QrContent;

use Carbon\Carbon;

final class CalendarBuilder
{
    /** @param array<string, mixed> $fields */
    public function build(array $fields): string
    {
        $title = $this->escape((string) ($fields['calendar_title'] ?? ''));
        $startRaw = (string) ($fields['calendar_start'] ?? '');

        if ($title === '' || $startRaw === '') {
            return '';
        }

        $allDay = (bool) ($fields['calendar_all_day'] ?? false);
        $endRaw = (string) ($fields['calendar_end'] ?? '');
        $description = $this->escape((string) ($fields['calendar_description'] ?? ''));
        $location = $this->escape((string) ($fields['calendar_location'] ?? ''));

        $uid = md5($title.$startRaw).'@qr-master.app';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//QR-Master//EN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTART'.($allDay ? ';VALUE=DATE:' : ':').$this->formatDt($startRaw, $allDay),
        ];

        if ($endRaw !== '') {
            $lines[] = 'DTEND'.($allDay ? ';VALUE=DATE:' : ':').$this->formatDt($endRaw, $allDay);
        }

        $lines[] = 'SUMMARY:'.$title;

        if ($description !== '') {
            $lines[] = 'DESCRIPTION:'.$description;
        }

        if ($location !== '') {
            $lines[] = 'LOCATION:'.$location;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    private function formatDt(string $datetime, bool $allDay): string
    {
        $carbon = Carbon::parse($datetime);

        return $allDay ? $carbon->format('Ymd') : $carbon->format('Ymd\THis');
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $value);
    }
}
