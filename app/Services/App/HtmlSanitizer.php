<?php

namespace App\Services\App;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Beitrags-HTML für die App bereinigen: nur Textformatierung, Listen, Links, Bilder und Videos.
 * Die App stellt dieses HTML ohne WebView dar; `<video>` spielt sie nativ ab, `<iframe>` (YouTube, Vimeo)
 * öffnet sie als Link im Browser. HTMLPurifier kennt die HTML5-Medien-Tags nicht, daher eigene Definition.
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (! self::$purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $cache = storage_path('framework/cache/htmlpurifier');
            if (! is_dir($cache)) {
                @mkdir($cache, 0775, true);
            }
            $config->set('Cache.SerializerPath', $cache);
            // Bei jeder Änderung an der Definition DefinitionRev erhöhen, sonst greift der Cache.
            $config->set('HTML.DefinitionID', 'elterninfo-app-posts');
            $config->set('HTML.DefinitionRev', 2);
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,a[href],ul,ol,li,h1,h2,h3,h4,blockquote,img[src|alt],table,tr,td,th,span,div,'
                .'video[src|poster|width|height],source[src|type],iframe[src|title|width|height]');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $config->set('Attr.AllowedFrameTargets', []);
            // Nur Einbettungen bekannter Videodienste; die App zeigt sie ohnehin nur als Link.
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^https://(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');

            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
                    'poster' => 'URI',
                    'width' => 'Length',
                    'height' => 'Length',
                ]);
                $def->addElement('source', 'Block', 'Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                ]);
            }

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier->purify($html);
    }
}
