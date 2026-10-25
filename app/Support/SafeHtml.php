<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\Cache;

/**
 * HTML aus Editor-Feldern (Beiträge, Rückmeldungen, Krankmeldungen, Listen, Seiten …) für die
 * Web-Ausgabe bereinigen. Erlaubt bleibt alles, was die Editoren erzeugen (Formatierung, Tabellen,
 * Bilder, Links, eingebettete Videos von YouTube/Vimeo); Skripte, Event-Handler, javascript:-Links,
 * Formulare und fremde iframes werden entfernt.
 *
 * In Blade: `@safeHtml($post->news)` statt `{!! $post->news !!}`.
 */
class SafeHtml
{
    /** Bei Änderungen an der Konfiguration erhöhen (Cache-Schlüssel und HTMLPurifier-Definition). */
    private const REVISION = 1;

    private static ?HTMLPurifier $purifier = null;

    public static function clean(mixed $html): string
    {
        $html = (string) $html;
        if ($html === '' || ! preg_match('/[<&]/', $html)) {
            return $html;
        }

        // HTMLPurifier ist langsam – bereinigtes Ergebnis je Inhalt eine Woche zwischenspeichern
        try {
            return Cache::remember('safehtml:'.self::REVISION.':'.sha1($html), now()->addWeek(), fn () => self::purifier()->purify($html));
        } catch (\Throwable) {
            return self::purifier()->purify($html);
        }
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $cache = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }
        $config->set('Cache.SerializerPath', $cache);
        $config->set('HTML.DefinitionID', 'elterninfoboard-web');
        $config->set('HTML.DefinitionRev', self::REVISION);
        $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
        // data: nur für eingefügte Bilder (HTMLPurifier lässt dort ausschließlich Bild-MIME-Typen zu)
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true, 'data' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('Attr.AllowedRel', ['noopener', 'noreferrer', 'nofollow']);
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^https://(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
        $config->set('AutoFormat.RemoveEmpty', false);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // HTML5-Elemente, die der Editor erzeugt (Medien-Plugin, Bildunterschriften)
            $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
            $def->addElement('mark', 'Inline', 'Inline', 'Common');
            $def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                'src' => 'URI', 'poster' => 'URI', 'width' => 'Length', 'height' => 'Length',
                'controls' => 'Bool', 'preload' => 'Enum#auto,metadata,none', 'loop' => 'Bool', 'muted' => 'Bool',
            ]);
            $def->addElement('audio', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                'src' => 'URI', 'controls' => 'Bool', 'preload' => 'Enum#auto,metadata,none', 'loop' => 'Bool',
            ]);
            $def->addElement('source', 'Block', 'Empty', 'Common', ['src' => 'URI', 'type' => 'Text']);
            $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
