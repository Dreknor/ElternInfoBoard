/**
 * First we will load all of this project's JavaScript dependencies.
 */

import './bootstrap';

/**
 * Alpine.js – wird von Livewire 3 automatisch injiziert und überschreibt window.Alpine.
 * Dieser Import dient nur als Fallback für Seiten OHNE Livewire-Komponenten.
 */
import Alpine from 'alpinejs';
if (typeof window.Alpine === 'undefined') {
    window.Alpine = Alpine;
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Livewire === 'undefined' && !window._alpineStarted) {
            window._alpineStarted = true;
            window.Alpine.start();
        }
    });
}
