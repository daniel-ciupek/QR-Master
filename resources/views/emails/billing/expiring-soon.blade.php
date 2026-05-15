@component('mail::message')
# {{ $isTrial ? 'Twój trial wygasa za 7 dni' : 'Subskrypcja wygasa za 7 dni' }}

@if($isTrial)
Twój bezpłatny trial planu **{{ $planName }}** wygasa **{{ $expiresAt }}**.

Przejdź na płatny plan, aby zachować dostęp do wszystkich funkcji premium.

@component('mail::button', ['url' => $upgradeUrl])
Wybierz plan
@endcomponent
@else
Twoja subskrypcja planu **{{ $planName }}** zostanie anulowana **{{ $expiresAt }}**.

Możesz wznowić subskrypcję w panelu rozliczeniowym.

@component('mail::button', ['url' => $portalUrl])
Wznów subskrypcję
@endcomponent
@endif

Zespół QR-Master
@endcomponent
