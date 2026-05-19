@component('mail::message')
# Płatność zakończona pomyślnie

Dziękujemy za odnowienie planu **{{ $planName }}** w QR-Master.

**Kwota:** {{ $amount }}

Twój plan jest aktywny. Możesz przeglądać faktury i zarządzać subskrypcją w panelu rozliczeniowym.

@component('mail::button', ['url' => $portalUrl])
Otwórz panel rozliczeniowy
@endcomponent

Dziękujemy za zaufanie,<br>
Zespół QR-Master
@endcomponent
