{{-- Kalender-Links (Google / iCal) für einen gebuchten Termin --}}
<a href="{{ $termin->link($liste->listenname)->google() }}" target="_blank" rel="noopener"
   class="inline-flex items-center gap-1 px-2 py-1.5 text-sm rounded-lg"
   style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);"
   title="In Google Kalender eintragen" aria-label="In Google Kalender eintragen">
    <img src="{{ asset('img/icon-google-cal.png') }}" height="16" width="16" alt="">
    <span class="hidden sm:inline">Google</span>
</a>
<a href="{{ $termin->link($liste->listenname)->ics() }}"
   class="inline-flex items-center gap-1 px-2 py-1.5 text-sm rounded-lg"
   style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);"
   title="Als iCal-Datei herunterladen" aria-label="Als iCal-Datei herunterladen">
    <img src="{{ asset('img/ics-icon.png') }}" height="16" width="16" alt="">
    <span class="hidden sm:inline">iCal</span>
</a>
