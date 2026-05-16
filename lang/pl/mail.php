<?php

declare(strict_types=1);

return [
    'footer' => [
        'rights' => 'Wszelkie prawa zastrzeżone.',
        'website' => 'qr-master.app',
    ],

    'verify_email' => [
        'subject' => 'Potwierdź swój adres email',
        'greeting' => 'Cześć!',
        'intro' => 'Kliknij poniższy przycisk, aby potwierdzić swój adres email i aktywować konto.',
        'action' => 'Potwierdź email',
        'outro' => 'Jeśli nie zakładałeś konta w QR-Master, możesz bezpiecznie zignorować tę wiadomość.',
        'subcopy' => 'Jeśli nie możesz kliknąć przycisku, skopiuj poniższy link do przeglądarki:',
        'expiry' => 'Link weryfikacyjny wygaśnie za :count minut.',
    ],

    'reset_password' => [
        'subject' => 'Reset hasła',
        'greeting' => 'Cześć!',
        'intro' => 'Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta. Kliknij przycisk poniżej.',
        'action' => 'Zresetuj hasło',
        'expiry' => 'Ten link wygaśnie za :count minut.',
        'outro' => 'Jeśli nie prosiłeś o reset hasła, nie musisz nic robić. Twoje hasło pozostanie bez zmian.',
        'subcopy' => 'Jeśli nie możesz kliknąć przycisku, skopiuj poniższy link do przeglądarki:',
    ],

    'invitation' => [
        'subject' => 'Zaproszenie do przestrzeni :team',
        'greeting' => 'Cześć!',
        'intro' => ':inviter zaprasza Cię do dołączenia do przestrzeni roboczej ":team" jako :role.',
        'action' => 'Zaakceptuj zaproszenie',
        'expiry' => 'Ten link zaproszenia wygaśnie po 7 dniach.',
        'outro' => 'Jeśli nie spodziewałeś się tego zaproszenia, możesz zignorować tę wiadomość.',
    ],
];
