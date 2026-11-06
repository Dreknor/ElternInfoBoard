<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Model\User;
use App\Services\CustomScripts\CustomScriptRenderer;
use App\Settings\CustomScriptSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Pflege des instanzspezifischen Snippets (z. B. Chatbot-Widget).
 *
 * Nur erreichbar, wenn die Instanz per CUSTOM_SCRIPTS_ENABLED freigeschaltet
 * ist; sonst 404. Zusätzlich ist "edit settings" nötig (Route-Middleware).
 */
class CustomScriptController extends Controller
{
    /** Tags, die das Seitengerüst zerstören würden. */
    private const FORBIDDEN_PATTERN = '~<\s*/?\s*(html|head|body)\b~i';

    public function edit(CustomScriptSetting $setting): View
    {
        $this->ensureEnabled();

        return view('settings.custom-scripts', [
            'setting' => $setting,
            'updatedBy' => $setting->updated_by ? User::find($setting->updated_by) : null,
            'maxLength' => (int) config('custom_scripts.max_length', 20000),
        ]);
    }

    public function update(Request $request, CustomScriptSetting $setting): RedirectResponse
    {
        $this->ensureEnabled();

        $validated = $request->validate([
            'active' => 'nullable|boolean',
            'snippet' => [
                'nullable',
                'string',
                'max:'.(int) config('custom_scripts.max_length', 20000),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && preg_match(self::FORBIDDEN_PATTERN, $value)) {
                        $fail('Das Snippet darf keine <html>-, <head>- oder <body>-Tags enthalten.');
                    }
                },
            ],
            'position' => ['required', Rule::in(CustomScriptRenderer::POSITIONS)],
            'show_for_guests' => 'nullable|boolean',
            'app_enabled' => 'nullable|boolean',
            'app_label' => 'nullable|string|max:30',
            'app_url' => 'nullable|url:https|max:500',
        ]);

        $snippet = trim((string) ($validated['snippet'] ?? ''));
        $active = $request->boolean('active');

        if ($active && $snippet === '') {
            return back()->withInput()->withErrors([
                'snippet' => 'Zum Aktivieren muss ein Snippet hinterlegt sein.',
            ]);
        }

        $changed = $snippet !== $setting->snippet;

        $setting->active = $active;
        $setting->snippet = $snippet;
        $setting->position = $validated['position'];
        $setting->show_for_guests = $request->boolean('show_for_guests');
        $setting->app_enabled = $request->boolean('app_enabled');
        $setting->app_label = trim((string) ($validated['app_label'] ?? '')) ?: 'Chat';
        $setting->app_url = ($validated['app_url'] ?? null) ?: null;
        $setting->updated_by = $request->user()->id;
        $setting->updated_at = now()->toIso8601String();
        $setting->save();

        Log::info('Eigene Skripte geändert', [
            'user_id' => $request->user()->id,
            'active' => $setting->active,
            'position' => $setting->position,
            'show_for_guests' => $setting->show_for_guests,
            'app_enabled' => $setting->app_enabled,
            'app_url' => $setting->app_url,
            'snippet_changed' => $changed,
            'snippet_sha256' => $snippet === '' ? null : hash('sha256', $snippet),
        ]);

        return redirect()->route('settings.custom-scripts.edit')->with([
            'type' => 'success',
            'Meldung' => 'Eigene Skripte gespeichert.',
        ]);
    }

    private function ensureEnabled(): void
    {
        abort_unless(CustomScriptRenderer::enabled(), 404);
    }
}
