<?php

namespace App\Services\Wordpress;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Support\Str;

/**
 * Wandelt das HTML aus dem TinyMCE-Editor in Gutenberg-Block-Markup um,
 * damit Beiträge in WordPress ohne Nacharbeit sauber dargestellt werden
 * (keine "Klassisch"-Blöcke mit Bootstrap-Klassen, keine ungültigen Blöcke,
 * keine relativen Links, keine leeren Absätze).
 *
 * Das Markup entspricht dem, was der Block-Editor ab WordPress 6.6 selbst speichert.
 * Unbekannte Elemente (z. B. Tabellen) werden als klassischer Inhalt übernommen,
 * der vom Editor nie als fehlerhaft markiert wird.
 */
class GutenbergConverter
{
    /** CSS-Eigenschaften, die in Inline-Formatierungen erhalten bleiben. */
    private const ALLOWED_STYLES = ['color', 'background-color', 'text-decoration'];

    /** Elemente, die innerhalb eines Absatzes stehen dürfen. */
    private const INLINE_TAGS = [
        'a', 'abbr', 'b', 'br', 'cite', 'code', 'del', 'em', 'i', 'img', 'ins', 'kbd', 'mark',
        'q', 's', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'u', 'font',
    ];

    /** Elemente, die samt Inhalt entfernt werden. */
    private const REMOVE_TAGS = ['script', 'style', 'noscript', 'form', 'input', 'button', 'select', 'textarea', 'meta', 'link'];

    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('app.url'), '/');
    }

    /**
     * Wandelt Editor-HTML in Block-Markup um.
     */
    public function convert(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        // Reiner Text ohne HTML: Absätze anhand von Leerzeilen bilden
        if (! preg_match('/<\/?[a-z][a-z0-9]*[\s>\/]/i', $html)) {
            return $this->convertPlainText($html);
        }

        $root = $this->parse($html);

        return $this->joinBlocks($this->convertChildren($root));
    }

    /**
     * Erzeugt einen Bild-Block für ein bereits nach WordPress hochgeladenes Bild.
     *
     * @param  array{id:int, url:string, full:string, size:string, alt:string, caption:string}  $image
     */
    public function imageBlock(array $image): string
    {
        $caption = $image['caption'] !== ''
            ? '<figcaption class="wp-element-caption">'.e($image['caption']).'</figcaption>'
            : '';

        $html = sprintf(
            '<figure class="wp-block-image size-%s"><a href="%s"><img src="%s" alt="%s" class="wp-image-%d"/></a>%s</figure>',
            $image['size'],
            e($image['full']),
            e($image['url']),
            e($image['alt']),
            $image['id'],
            $caption
        );

        return $this->block('image', [
            'id' => $image['id'],
            'sizeSlug' => $image['size'],
            'linkDestination' => 'media',
        ], $html);
    }

    /**
     * Erzeugt aus mehreren Bildern eine Galerie, aus einem einzelnen Bild einen Bild-Block.
     *
     * @param  array<int, array{id:int, url:string, full:string, size:string, alt:string, caption:string}>  $images
     */
    public function galleryBlock(array $images): string
    {
        if (count($images) === 0) {
            return '';
        }

        if (count($images) === 1) {
            return $this->imageBlock(array_values($images)[0]);
        }

        $inner = implode("\n\n", array_map(fn (array $image) => $this->imageBlock($image), $images));

        return $this->block(
            'gallery',
            ['linkTo' => 'media'],
            '<figure class="wp-block-gallery has-nested-images columns-default is-cropped">'.$inner.'</figure>'
        );
    }

    /**
     * Erzeugt einen Datei-Block (Download) für eine nach WordPress hochgeladene Datei.
     */
    public function fileBlock(int $id, string $url, string $name): string
    {
        $anchorId = 'wp-block-file--media-'.Str::uuid();

        $html = sprintf(
            '<div class="wp-block-file"><a id="%1$s" href="%2$s">%3$s</a><a href="%2$s" class="wp-block-file__button wp-element-button" download aria-describedby="%1$s">Herunterladen</a></div>',
            $anchorId,
            e($url),
            e($name)
        );

        return $this->block('file', ['id' => $id, 'href' => $url], $html);
    }

    /**
     * Erzeugt einen kurzen Klartext-Auszug (für Übersichtsseiten des Themes).
     */
    public function excerpt(?string $html, int $words = 40): string
    {
        $text = preg_replace('/<(br|\/p|\/h[1-6]|\/li|\/div)[^>]*>/i', ' ', (string) $html);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)));

        return Str::words($text, $words, ' …');
    }

    /**
     * Fügt Blöcke zu einem Inhalt zusammen.
     *
     * @param  array<int, string>  $blocks
     */
    public function joinBlocks(array $blocks): string
    {
        return implode("\n\n", array_filter($blocks, fn ($block) => trim($block) !== ''));
    }

    /**
     * Serialisiert einen Block im Format des Block-Editors.
     */
    public function block(string $name, array $attributes, string $html): string
    {
        $json = '';

        if ($attributes !== []) {
            $json = ' '.str_replace(
                '--',
                '\\u002d\\u002d',
                json_encode($attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT)
            );
        }

        return "<!-- wp:{$name}{$json} -->\n{$html}\n<!-- /wp:{$name} -->";
    }

    private function convertPlainText(string $text): string
    {
        $paragraphs = preg_split('/\R\s*\R/u', str_replace("\r\n", "\n", $text));
        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $blocks[] = $this->paragraph(nl2br(e($paragraph, false), false));
            }
        }

        return $this->joinBlocks($blocks);
    }

    private function parse(string $html): DOMElement
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="wp-converter-root">'.$html.'</div></body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document->getElementById('wp-converter-root');
    }

    /**
     * Wandelt alle Kindknoten eines Containers in Blöcke um. Lose Inline-Inhalte
     * (Text, Links, Fettdruck …) werden zu Absätzen zusammengefasst.
     *
     * @return array<int, string>
     */
    private function convertChildren(DOMNode $container): array
    {
        $blocks = [];
        $inline = [];

        $flushInline = function () use (&$inline, &$blocks) {
            if ($inline !== []) {
                $blocks[] = $this->paragraphFromNodes($inline);
                $inline = [];
            }
        };

        foreach (iterator_to_array($container->childNodes) as $node) {
            if ($node instanceof DOMText) {
                $inline[] = $node;

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::REMOVE_TAGS, true)) {
                continue;
            }

            if (in_array($tag, self::INLINE_TAGS, true) && ! $this->isStandaloneImage($node)) {
                $inline[] = $node;

                continue;
            }

            $flushInline();
            $blocks = array_merge($blocks, $this->convertElement($node, $tag));
        }

        $flushInline();

        return array_values(array_filter($blocks, fn ($block) => $block !== ''));
    }

    /**
     * @return array<int, string>
     */
    private function convertElement(DOMElement $node, string $tag): array
    {
        return match (true) {
            $tag === 'p' => [$this->convertParagraph($node)],
            (bool) preg_match('/^h[1-6]$/', $tag) => [$this->convertHeading($node, (int) substr($tag, 1))],
            $tag === 'ul', $tag === 'ol' => [$this->convertList($node)],
            $tag === 'hr' => [$this->block('separator', [], '<hr class="wp-block-separator has-alpha-channel-opacity"/>')],
            $tag === 'blockquote' => [$this->convertQuote($node)],
            $tag === 'img' => [$this->convertImage($node)],
            $tag === 'iframe' => [$this->convertIframe($node)],
            $tag === 'table' => [$this->convertTable($node)],
            // Container (z. B. eingefügte Inhalte aus Word oder Webseiten) auflösen
            in_array($tag, ['div', 'section', 'article', 'header', 'footer', 'main', 'center', 'figure'], true) => $this->convertChildren($node),
            default => [$this->freeform($node)],
        };
    }

    private function convertParagraph(DOMElement $node): string
    {
        // Eingebettete Videos stehen bei TinyMCE in einem eigenen Absatz
        $iframe = $this->onlyChild($node, 'iframe');
        if ($iframe) {
            return $this->convertIframe($iframe);
        }

        $image = $this->onlyChild($node, 'img');
        if ($image) {
            return $this->convertImage($image);
        }

        // Absatz mit Bootstrap-Button (z. B. "Button groß") wird zum WordPress-Button
        $link = $this->onlyChild($node, 'a');
        if ($link && Str::contains((string) $link->getAttribute('class'), 'btn')) {
            return $this->convertButton($link, $this->textAlign($node));
        }

        // Absätze mit verschachtelten Block-Elementen (ungültiges HTML) auflösen
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && ! in_array(strtolower($child->tagName), self::INLINE_TAGS, true)) {
                return $this->joinBlocks($this->convertChildren($node));
            }
        }

        return $this->paragraph($this->innerHtml($node), $this->textAlign($node));
    }

    /**
     * @param  array<int, DOMNode>  $nodes
     */
    private function paragraphFromNodes(array $nodes): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $html .= $this->outerHtml($node);
        }

        return $this->paragraph($html);
    }

    private function paragraph(string $html, ?string $align = null): string
    {
        $html = $this->trimHtml($html);

        if ($this->isEmptyHtml($html)) {
            return '';
        }

        if ($align) {
            return $this->block('paragraph', ['align' => $align], '<p class="has-text-align-'.$align.'">'.$html.'</p>');
        }

        return $this->block('paragraph', [], '<p>'.$html.'</p>');
    }

    private function convertHeading(DOMElement $node, int $level): string
    {
        // Die Überschrift 1 ist in WordPress dem Beitragstitel vorbehalten
        $level = max(2, $level);
        $html = $this->trimHtml($this->innerHtml($node));

        if ($this->isEmptyHtml($html)) {
            return '';
        }

        $align = $this->textAlign($node);
        $attributes = [];
        $class = 'wp-block-heading';

        if ($align) {
            $attributes['textAlign'] = $align;
            $class .= ' has-text-align-'.$align;
        }

        if ($level !== 2) {
            $attributes['level'] = $level;
        }

        return $this->block('heading', $attributes, "<h{$level} class=\"{$class}\">{$html}</h{$level}>");
    }

    private function convertList(DOMElement $node): string
    {
        $ordered = strtolower($node->tagName) === 'ol';
        $items = [];

        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $childTag = strtolower($child->tagName);

            if ($childTag === 'li') {
                $items[] = $this->convertListItem($child);
            } elseif ($childTag === 'ul' || $childTag === 'ol') {
                // Fehlerhaft verschachtelte Liste ohne umgebendes <li>
                $items[] = $this->block('list-item', [], '<li>'.$this->convertList($child).'</li>');
            }
        }

        $items = array_filter($items);

        if ($items === []) {
            return '';
        }

        $tag = $ordered ? 'ol' : 'ul';
        $html = "<{$tag} class=\"wp-block-list\">".implode("\n\n", $items)."</{$tag}>";

        return $this->block('list', $ordered ? ['ordered' => true] : [], $html);
    }

    private function convertListItem(DOMElement $node): string
    {
        $content = '';
        $nested = '';

        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                $nested .= $this->convertList($child);
            } elseif ($child instanceof DOMElement && strtolower($child->tagName) === 'p') {
                // <li><p>…</p></li> aus Word-Einfügungen
                $content .= ($content !== '' ? '<br>' : '').$this->innerHtml($child);
            } else {
                $content .= $this->outerHtml($child);
            }
        }

        $content = $this->trimHtml($content);

        if ($this->isEmptyHtml($content) && $nested === '') {
            return '';
        }

        return $this->block('list-item', [], '<li>'.$content.$nested.'</li>');
    }

    private function convertQuote(DOMElement $node): string
    {
        $inner = $this->joinBlocks($this->convertChildren($node));

        if ($inner === '') {
            return '';
        }

        return $this->block('quote', [], '<blockquote class="wp-block-quote">'.$inner.'</blockquote>');
    }

    private function convertButton(DOMElement $link, ?string $align): string
    {
        $text = $this->trimHtml($this->innerHtml($link));
        $href = $this->absoluteUrl($link->getAttribute('href'));

        $button = $this->block(
            'button',
            [],
            '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.e($href).'">'.$text.'</a></div>'
        );

        $attributes = [];
        $class = 'wp-block-buttons';

        if ($align === 'center' || $align === 'right') {
            $justify = $align === 'center' ? 'center' : 'right';
            $attributes['layout'] = ['type' => 'flex', 'justifyContent' => $justify];
            $class .= ' is-content-justification-'.$justify.' is-layout-flex';
        }

        return $this->block('buttons', $attributes, '<div class="'.$class.'">'.$button.'</div>');
    }

    private function convertImage(DOMElement $img): string
    {
        $src = $this->absoluteUrl($img->getAttribute('src'));

        // Eingebettete Base64-Bilder blähen den Beitrag auf und werden von WordPress oft entfernt
        if ($src === '' || Str::startsWith($src, 'data:')) {
            return '';
        }

        $alt = e($img->getAttribute('alt'));

        return $this->block('image', [], '<figure class="wp-block-image"><img src="'.e($src).'" alt="'.$alt.'"/></figure>');
    }

    private function convertIframe(DOMElement $iframe): string
    {
        $src = $this->absoluteUrl($iframe->getAttribute('src'));

        if ($src === '') {
            return '';
        }

        // YouTube und Vimeo als oEmbed: wird von WordPress responsiv eingebunden
        // und auch ohne "unfiltered_html"-Recht nicht herausgefiltert.
        if (preg_match('~youtube(?:-nocookie)?\.com/embed/([\w-]{6,})~i', $src, $match)) {
            return $this->embed('https://www.youtube.com/watch?v='.$match[1], 'youtube', 'YouTube');
        }

        if (preg_match('~player\.vimeo\.com/video/(\d+)~i', $src, $match)) {
            return $this->embed('https://vimeo.com/'.$match[1], 'vimeo', 'Vimeo');
        }

        return $this->block('html', [], $this->outerHtml($iframe));
    }

    private function embed(string $url, string $provider, string $providerName): string
    {
        return $this->block('embed', [
            'url' => $url,
            'type' => 'video',
            'providerNameSlug' => $provider,
            'responsive' => true,
            'className' => 'wp-embed-aspect-16-9 wp-has-aspect-ratio',
        ], '<figure class="wp-block-embed is-type-video is-provider-'.$provider.' wp-block-embed-'.$provider.' wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">'."\n".e($url)."\n".'</div></figure>');
    }

    private function convertTable(DOMElement $table): string
    {
        // Feste Breiten, Rahmen und Farben aus dem Editor entfernen, damit das Theme die Tabelle gestaltet
        foreach (['width', 'height', 'border', 'cellpadding', 'cellspacing', 'bgcolor', 'align', 'style', 'class'] as $attribute) {
            $table->removeAttribute($attribute);
        }

        foreach ($table->getElementsByTagName('*') as $element) {
            foreach (['width', 'height', 'style', 'class', 'bgcolor', 'valign', 'align'] as $attribute) {
                $element->removeAttribute($attribute);
            }
        }

        return '<figure class="wp-block-table">'.$this->outerHtml($table).'</figure>';
    }

    /**
     * Übernimmt ein Element unverändert (bereinigt) als klassischen Inhalt.
     */
    private function freeform(DOMElement $node): string
    {
        $html = trim($this->outerHtml($node));

        return $this->isEmptyHtml($html) ? '' : $html;
    }

    private function isStandaloneImage(DOMElement $node): bool
    {
        return strtolower($node->tagName) === 'img';
    }

    /**
     * Liefert das einzige Kind-Element mit dem angegebenen Tag, wenn sonst nur Leerraum vorhanden ist.
     */
    private function onlyChild(DOMElement $node, string $tag): ?DOMElement
    {
        $found = null;

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                if (trim(str_replace("\u{00A0}", '', $child->textContent)) !== '') {
                    return null;
                }

                continue;
            }

            if ($child instanceof DOMElement) {
                if (strtolower($child->tagName) === 'br') {
                    continue;
                }

                if ($found !== null || strtolower($child->tagName) !== $tag) {
                    return null;
                }

                $found = $child;
            }
        }

        return $found;
    }

    private function textAlign(DOMElement $node): ?string
    {
        $style = strtolower((string) $node->getAttribute('style'));

        if (preg_match('/text-align\s*:\s*(center|right|justify)/', $style, $match)) {
            return $match[1];
        }

        $align = strtolower((string) $node->getAttribute('align'));

        return in_array($align, ['center', 'right', 'justify'], true) ? $align : null;
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';

        foreach (iterator_to_array($node->childNodes) as $child) {
            $html .= $this->outerHtml($child);
        }

        return $html;
    }

    /**
     * Gibt einen Knoten als HTML aus, nachdem Attribute bereinigt wurden.
     */
    private function outerHtml(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->textContent, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::REMOVE_TAGS, true)) {
            return '';
        }

        $this->cleanElement($node);

        // <font> und attributlose <span> tragen nach der Bereinigung keine Information mehr
        if ($tag === 'font' || ($tag === 'span' && ! $node->hasAttributes())) {
            return $this->innerHtml($node);
        }

        return $node->ownerDocument->saveHTML($node);
    }

    private function cleanElement(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            $keep = match (true) {
                $name === 'href', $name === 'src' => true,
                $name === 'style' => true,
                in_array($name, ['alt', 'title', 'target', 'rel', 'colspan', 'rowspan', 'scope', 'start', 'type', 'reversed', 'width', 'height', 'allow', 'allowfullscreen', 'frameborder', 'lang'], true) => true,
                default => false,
            };

            if (! $keep || Str::startsWith($name, 'on')) {
                $element->removeAttribute($attribute->name);
            }
        }

        foreach (['href', 'src'] as $urlAttribute) {
            if ($element->hasAttribute($urlAttribute)) {
                $url = $this->absoluteUrl($element->getAttribute($urlAttribute));

                if ($url === '' || Str::startsWith(strtolower($url), 'javascript:')) {
                    $element->removeAttribute($urlAttribute);
                } else {
                    $element->setAttribute($urlAttribute, $url);
                }
            }
        }

        if ($element->hasAttribute('style')) {
            $style = $this->filterStyle($element->getAttribute('style'));
            $style === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $style);
        }

        // Externe Links in neuem Tab sicher öffnen
        if ($element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noreferrer noopener');
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                if (in_array(strtolower($child->tagName), self::REMOVE_TAGS, true)) {
                    $element->removeChild($child);
                } else {
                    $this->cleanElement($child);
                }
            }
        }
    }

    private function filterStyle(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $value = trim($value);

            if ($value !== '' && in_array($property, self::ALLOWED_STYLES, true)) {
                $kept[] = $property.': '.$value;
            }
        }

        return implode('; ', $kept);
    }

    /**
     * Macht relative Links (TinyMCE erzeugt z. B. "../listen") absolut,
     * da sie auf der WordPress-Seite sonst ins Leere führen.
     */
    private function absoluteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || preg_match('~^([a-z][a-z0-9+.-]*:|#)~i', $url)) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:'.$url;
        }

        $path = ltrim(preg_replace('~^(\./|\.\./)+~', '', $url), '/');

        return $this->baseUrl.'/'.$path;
    }

    private function trimHtml(string $html): string
    {
        // Führende/abschließende Umbrüche und geschützte Leerzeichen entfernen
        $pattern = '(?:\s|&nbsp;|\x{00A0}|<br\s*\/?>)';

        return preg_replace('/^'.$pattern.'+|'.$pattern.'+$/u', '', trim($html)) ?? trim($html);
    }

    private function isEmptyHtml(string $html): bool
    {
        if (preg_match('/<(img|iframe|video|audio|object|embed|table|hr)\b/i', $html)) {
            return false;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", '', $text)) === '';
    }
}
