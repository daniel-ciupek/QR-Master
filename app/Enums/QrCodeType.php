<?php

declare(strict_types=1);

namespace App\Enums;

enum QrCodeType: string
{
    case Url = 'url';
    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Sms = 'sms';
    case Wifi = 'wifi';
    case VCard = 'vcard';
    case Geo = 'geo';
    case App = 'app';
    case BioLink = 'bio_link';
    case Pdf = 'pdf';
    case Calendar = 'calendar';
    case Crypto = 'crypto';
    case Review = 'review';
}
