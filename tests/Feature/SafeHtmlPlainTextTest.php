<?php

namespace Tests\Feature;

use App\Mail\Krankmeldung;
use App\Mail\UserRueckmeldung;
use App\Support\SafeHtml;
use Tests\TestCase;

/**
 * Klartext aus der Eltern-App enthält keine HTML-Tags – Zeilenumbrüche müssen in Mails und Web-Ansichten erhalten bleiben.
 */
class SafeHtmlPlainTextTest extends TestCase
{
    /** @test */
    public function plain_text_keeps_line_breaks_and_is_escaped(): void
    {
        $this->assertSame("Hallo,<br>\n<br>\nZeile 2 &lt; 3 &amp; &quot;4&quot;", SafeHtml::clean("Hallo,\n\nZeile 2 < 3 & \"4\""));
        $this->assertSame('Nur Text', SafeHtml::clean('Nur Text'));
        $this->assertSame('Tom &amp; Jerry', SafeHtml::clean('Tom &amp; Jerry'));
    }

    /** @test */
    public function editor_html_is_not_changed_into_line_breaks(): void
    {
        $clean = SafeHtml::clean("<p>Absatz 1</p>\n<p>Absatz 2</p>");

        $this->assertStringContainsString('<p>Absatz 1</p>', $clean);
        $this->assertStringNotContainsString('<br', $clean);
    }

    /** @test */
    public function app_texts_keep_line_breaks_in_mails(): void
    {
        $krankmeldung = (new Krankmeldung('eltern@example.org', 'Eltern', 'Kind', '01.10.2026', '02.10.2026', "Fieber\nseit gestern", null))->render();
        $this->assertStringContainsString("Fieber<br>\nseit gestern", $krankmeldung);

        $rueckmeldung = (new UserRueckmeldung(['email' => 'eltern@example.org', 'name' => 'Eltern', 'subject' => 'Rückmeldung', 'text' => "Ja\n\nDanke"]))->render();
        $this->assertStringContainsString("Ja<br>\n<br>\nDanke", $rueckmeldung);
    }
}
