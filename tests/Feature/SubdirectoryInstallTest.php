<?php

namespace Tests\Feature;

use App\Model\User;
use Tests\TestCase;

/**
 * Betrieb in einem Unterverzeichnis (z. B. https://schule.de/elterninfoboard/public/):
 * Livewire-Script, Livewire-Update-URL, Schrift und JS-Helfer müssen den
 * Installationspfad enthalten statt auf die Domain-Wurzel zu zeigen.
 */
class SubdirectoryInstallTest extends TestCase
{
    private const BASE = '/elterninfoboard/public';

    private function inSubdirectory(): static
    {
        return $this->withServerVariables([
            'SCRIPT_NAME' => self::BASE.'/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
            'PHP_SELF' => self::BASE.'/index.php',
        ]);
    }

    private function user(): User
    {
        return User::factory()->create(['changePassword' => false]);
    }

    public function test_page_in_subdirectory_uses_install_path(): void
    {
        $response = $this->inSubdirectory()
            ->actingAs($this->user())
            ->get('http://localhost'.self::BASE.'/hilfe');

        $response->assertOk();
        $html = $response->getContent();

        // Livewire-Script und Update-URL unter dem Installationspfad
        $this->assertStringContainsString('src="'.self::BASE.'/livewire/livewire', $html);
        $this->assertStringContainsString('data-update-uri="'.self::BASE.'/livewire/update"', $html);
        // Das eigene data-update-uri steht vor dem von Livewire (Livewire nimmt das erste)
        $this->assertLessThan(
            strpos($html, 'data-update-uri="/livewire/update"') ?: PHP_INT_MAX,
            strpos($html, 'data-update-uri="'.self::BASE.'/livewire/update"')
        );

        // JS-Helfer und Schrift
        $this->assertStringContainsString('<meta name="app-base-path" content="'.self::BASE.'">', $html);
        $this->assertStringContainsString(self::BASE.'/fonts/Newsreader-VariableFont_opsz,wght.ttf', $html);
    }

    public function test_page_in_domain_root_keeps_default_paths(): void
    {
        $response = $this->actingAs($this->user())->get('/hilfe');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('src="/livewire/livewire', $html);
        $this->assertStringNotContainsString('name="livewire-update-uri"', $html);
        $this->assertStringContainsString('<meta name="app-base-path" content="">', $html);
    }

    public function test_livewire_routes_answer_in_subdirectory(): void
    {
        $this->inSubdirectory()
            ->get('http://localhost'.self::BASE.'/livewire/livewire.min.js')
            ->assertOk();
    }
}
