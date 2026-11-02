<?php

namespace Tests\Unit;

use App\Services\App\HtmlSanitizer;
use Tests\TestCase;

/**
 * Beitrags-HTML für die App: Videos müssen ankommen, sonst zeigt die App nur den Ersatztext.
 */
class HtmlSanitizerTest extends TestCase
{
    /** @test */
    public function video_with_source_survives_for_the_app(): void
    {
        $html = HtmlSanitizer::clean('<p><video preload="metadata" controls="controls" width="100%" height="150">'
            .'<source src="https://cloud.example.org/s/abc/download" type="video/mp4" />'
            .'Ihr Browser unterstützt das Video-Tag nicht.</video></p>');

        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('<source src="https://cloud.example.org/s/abc/download" type="video/mp4"', $html);
    }

    /** @test */
    public function only_known_video_embeds_and_safe_urls_are_kept(): void
    {
        $html = HtmlSanitizer::clean('<iframe src="https://www.youtube.com/embed/abc"></iframe>'
            .'<iframe src="https://evil.example/x"></iframe>'
            .'<video src="javascript:alert(1)" onerror="x()"></video><script>alert(1)</script>');

        $this->assertStringContainsString('src="https://www.youtube.com/embed/abc"', $html);
        $this->assertStringNotContainsString('evil.example', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}
