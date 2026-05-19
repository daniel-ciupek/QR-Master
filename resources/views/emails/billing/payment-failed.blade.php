@component('mail::message')
# Problem z płatnością

Niestety nie udało się pobrać opłaty za plan **{{ $planName }}**.

Aby zachować dostęp do premium funkcji, zaktualizuj dane płatnicze w panelu rozliczeniowym.

@component('mail::button', ['url' => $portalUrl, 'color' => 'red'])
Zaktualizuj dane płatnicze
@endcomponent

Jeśli płatność nie zostanie uregulowana w ciągu kilku dni, Twoje konto zostanie przeniesione na plan Free.

Zespół QR-Master
@endcomponent
