<?php

namespace App\Services\App;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Beitrags-HTML für die App bereinigen: nur Textformatierung, Listen, Links und Bilder.
 * Die App stellt dieses HTML ohne WebView dar.
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
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,a[href],ul,ol,li,h1,h2,h3,h4,blockquote,img[src|alt],table,tr,td,th,span,div');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $config->set('Attr.AllowedFrameTargets', []);
            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier->purify($html);
    }
}
