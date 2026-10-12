<?php

namespace Tests\Unit\Services;

use App\Services\Wordpress\GutenbergConverter;
use Tests\TestCase;

class GutenbergConverterTest extends TestCase
{
    private GutenbergConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new GutenbergConverter('https://eltern.example.de');
    }

    /** @test */
    public function paragraphs_become_paragraph_blocks_and_empty_ones_are_removed(): void
    {
        $result = $this->converter->convert('<p>Liebe Eltern,</p><p>&nbsp;</p><p><br></p><p>schöne Grüße</p>');

        $this->assertSame(
            "<!-- wp:paragraph -->\n<p>Liebe Eltern,</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>schöne Grüße</p>\n<!-- /wp:paragraph -->",
            $result
        );
    }

    /** @test */
    public function text_alignment_is_converted_to_block_attribute(): void
    {
        $result = $this->converter->convert('<p style="text-align: center; padding-left: 30px;">Mitte</p>');

        $this->assertStringContainsString('<!-- wp:paragraph {"align":"center"} -->', $result);
        $this->assertStringContainsString('<p class="has-text-align-center">Mitte</p>', $result);
        $this->assertStringNotContainsString('padding', $result);
    }

    /** @test */
    public function headings_are_converted_and_h1_is_demoted(): void
    {
        $result = $this->converter->convert('<h1>Titel</h1><h3>Unterpunkt</h3>');

        $this->assertStringContainsString("<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Titel</h2>", $result);
        $this->assertStringContainsString("<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Unterpunkt</h3>", $result);
    }

    /** @test */
    public function nested_lists_become_list_blocks_with_list_items(): void
    {
        $result = $this->converter->convert('<ol><li>Eins</li><li>Zwei<ul><li>Zwei-A</li></ul></li></ol>');

        $this->assertStringStartsWith("<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">", $result);
        $this->assertSame(3, substr_count($result, '<!-- wp:list-item -->'));
        $this->assertStringContainsString('<li>Zwei<!-- wp:list -->', $result);
    }

    /** @test */
    public function inline_styles_are_reduced_to_colors_and_fonts_are_removed(): void
    {
        $result = $this->converter->convert('<p><span style="font-family: Comic Sans; font-size: 18pt;">Text</span> <strong style="color: #c00; font-size: 20px;">rot</strong></p>');

        $this->assertStringContainsString('<p>Text <strong style="color: #c00">rot</strong></p>', $result);
    }

    /** @test */
    public function relative_links_are_made_absolute(): void
    {
        $result = $this->converter->convert('<p><a href="../../listen">Listen</a> <a href="/files">Dateien</a> <a href="https://extern.de">extern</a></p>');

        $this->assertStringContainsString('href="https://eltern.example.de/listen"', $result);
        $this->assertStringContainsString('href="https://eltern.example.de/files"', $result);
        $this->assertStringContainsString('href="https://extern.de"', $result);
    }

    /** @test */
    public function bootstrap_buttons_become_wordpress_buttons(): void
    {
        $result = $this->converter->convert('<p><a class="btn btn-primary btn-block" href="https://eltern.example.de/listen">Eintragen</a></p>');

        $this->assertStringContainsString('<!-- wp:buttons -->', $result);
        $this->assertStringContainsString('<a class="wp-block-button__link wp-element-button" href="https://eltern.example.de/listen">Eintragen</a>', $result);
        $this->assertStringNotContainsString('btn-primary', $result);
    }

    /** @test */
    public function youtube_iframes_become_embed_blocks(): void
    {
        $result = $this->converter->convert('<p><iframe src="//www.youtube.com/embed/abcdefghijk" width="560" height="314"></iframe></p>');

        $this->assertStringContainsString('<!-- wp:embed {"url":"https://www.youtube.com/watch?v=abcdefghijk"', $result);
        $this->assertStringNotContainsString('<iframe', $result);
    }

    /** @test */
    public function scripts_and_event_handlers_are_removed(): void
    {
        $result = $this->converter->convert('<p onclick="alert(1)">Hallo<script>alert(2)</script></p><script>alert(3)</script>');

        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('<p>Hallo</p>', $result);
    }

    /** @test */
    public function tables_are_kept_without_fixed_layout(): void
    {
        $result = $this->converter->convert('<table border="1" style="width: 100%;"><tbody><tr><td style="width: 50%;">A</td></tr></tbody></table>');

        $this->assertSame('<figure class="wp-block-table"><table><tbody><tr><td>A</td></tr></tbody></table></figure>', $result);
    }

    /** @test */
    public function loose_text_and_plain_text_become_paragraphs(): void
    {
        $this->assertSame(
            "<!-- wp:paragraph -->\n<p>Zeile 1<br>\nZeile 2</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Tom &amp; Jerry &lt;3</p>\n<!-- /wp:paragraph -->",
            $this->converter->convert("Zeile 1\nZeile 2\n\nTom & Jerry <3")
        );

        $this->assertStringContainsString('<p>Loser <em>Text</em></p>', $this->converter->convert('<h2>A</h2>Loser <em>Text</em>'));
    }

    /** @test */
    public function gallery_contains_nested_image_blocks(): void
    {
        $image = fn (int $id) => [
            'id' => $id,
            'url' => "https://wp.test/{$id}-1024.jpg",
            'full' => "https://wp.test/{$id}.jpg",
            'size' => 'large',
            'alt' => 'Bild zu: Sommerfest',
            'caption' => '',
        ];

        $single = $this->converter->galleryBlock([$image(1)]);
        $this->assertStringStartsWith('<!-- wp:image {"id":1,"sizeSlug":"large","linkDestination":"media"} -->', $single);
        $this->assertStringContainsString('<img src="https://wp.test/1-1024.jpg" alt="Bild zu: Sommerfest" class="wp-image-1"/>', $single);

        $gallery = $this->converter->galleryBlock([$image(1), $image(2)]);
        $this->assertStringStartsWith("<!-- wp:gallery {\"linkTo\":\"media\"} -->\n<figure class=\"wp-block-gallery has-nested-images columns-default is-cropped\">", $gallery);
        $this->assertSame(2, substr_count($gallery, '<!-- wp:image '));
    }

    /** @test */
    public function excerpt_is_plain_text(): void
    {
        $this->assertSame('Hallo Welt. Zweiter Absatz', $this->converter->excerpt('<p>Hallo&nbsp;Welt.</p><p>Zweiter <strong>Absatz</strong></p>'));
    }
}
