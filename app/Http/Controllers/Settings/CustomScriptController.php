<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Model\CustomScript;
use App\Services\CustomScripts\CustomScriptRenderer;
use App\Settings\CustomScriptSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

/**
 * Pflege der eigenen Skripte (z. B. Chatbot-Widget, Gag in der Anwesenheitsliste).
 *
 * Nur erreichbar, wenn die Instanz per CUSTOM_SCRIPTS_ENABLED freigeschaltet
 * ist; sonst 404. Zusätzlich ist "edit settings" nötig (Route-Middleware).
 */
class CustomScriptController extends Controller
{
    /** Tags, die das Seitengerüst zerstören würden. */
    private const FORBIDDEN_PATTERN = '~<\s*/?\s*(html|head|body)\b~i';

    public function index(CustomScriptSetting $setting): View
    {
        $this->ensureEnabled();

        return view('settings.custom-scripts.index', [
            'scripts' => CustomScript::query()->with(['roles', 'editor'])->ordered()->get(),
            'setting' => $setting,
        ]);
    }

    public function create(): View
    {
        $this->ensureEnabled();

        return $this->form(new CustomScript([
            'active' => true,
            'position' => 'body',
            'page_mode' => CustomScript::PAGE_ALL,
            'for_guests' => false,
            'for_users' => true,
            'for_students' => false,
            'restrict_roles' => false,
            'use_on_chat' => false,
            'sort_order' => ((int) CustomScript::query()->max('sort_order')) + 10,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        $script = new CustomScript;
        $this->fillAndSave($script, $request);

        $this->log('angelegt', $script, $request);

        return redirect()->route('settings.custom-scripts.index')->with([
            'type' => 'success',
            'Meldung' => "Skript „{$script->name}“ angelegt.",
        ]);
    }

    public function edit(CustomScript $customScript): View
    {
        $this->ensureEnabled();

        return $this->form($customScript->load(['roles', 'editor']));
    }

    public function update(Request $request, CustomScript $customScript): RedirectResponse
    {
        $this->ensureEnabled();

        $oldHash = hash('sha256', $customScript->snippet);
        $this->fillAndSave($customScript, $request);

        $this->log('geändert', $customScript, $request, ['snippet_changed' => $oldHash !== hash('sha256', $customScript->snippet)]);

        return redirect()->route('settings.custom-scripts.index')->with([
            'type' => 'success',
            'Meldung' => "Skript „{$customScript->name}“ gespeichert.",
        ]);
    }

    /**
     * Schnell aktivieren/deaktivieren aus der Übersicht.
     */
    public function toggle(Request $request, CustomScript $customScript): RedirectResponse
    {
        $this->ensureEnabled();

        $activate = ! $customScript->active;

        if ($activate && $customScript->restrict_roles && ! $customScript->roles()->exists()) {
            return redirect()->route('settings.custom-scripts.index')->with([
                'type' => 'danger',
                'Meldung' => "Skript „{$customScript->name}“ ist auf Rollen begrenzt, hat aber keine Rolle mehr. Bitte zuerst eine Rolle auswählen.",
            ]);
        }

        $customScript->active = $activate;
        $customScript->disabled_reason = null;
        $customScript->updated_by = $request->user()->id;
        $customScript->save();

        $this->log($activate ? 'aktiviert' : 'deaktiviert', $customScript, $request);

        return redirect()->route('settings.custom-scripts.index')->with([
            'type' => 'success',
            'Meldung' => "Skript „{$customScript->name}“ ".($activate ? 'aktiviert.' : 'deaktiviert.'),
        ]);
    }

    public function destroy(Request $request, CustomScript $customScript): RedirectResponse
    {
        $this->ensureEnabled();

        $this->log('gelöscht', $customScript, $request);
        $name = $customScript->name;
        $customScript->delete();

        return redirect()->route('settings.custom-scripts.index')->with([
            'type' => 'success',
            'Meldung' => "Skript „{$name}“ gelöscht.",
        ]);
    }

    /**
     * Globale Einstellungen für den Chat-Button der Eltern-App.
     */
    public function updateApp(Request $request, CustomScriptSetting $setting): RedirectResponse
    {
        $this->ensureEnabled();

        $validated = $request->validate([
            'app_enabled' => 'nullable|boolean',
            'app_label' => 'nullable|string|max:30',
        ]);

        $setting->app_enabled = $request->boolean('app_enabled');
        $setting->app_label = trim((string) ($validated['app_label'] ?? '')) ?: 'Chatbot';
        $setting->save();

        Log::info('Eigene Skripte: App-Chat geändert', [
            'user_id' => $request->user()->id,
            'app_enabled' => $setting->app_enabled,
        ]);

        return redirect()->route('settings.custom-scripts.index')->with([
            'type' => 'success',
            'Meldung' => 'Einstellungen für die App gespeichert.',
        ]);
    }

    private function form(CustomScript $script): View
    {
        return view('settings.custom-scripts.form', [
            'script' => $script,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'maxLength' => (int) config('custom_scripts.max_length', 20000),
        ]);
    }

    private function fillAndSave(CustomScript $script, Request $request): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');

        $validator = validator($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'active' => 'nullable|boolean',
            'snippet' => [
                'required',
                'string',
                'max:'.(int) config('custom_scripts.max_length', 20000),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_string($value) && preg_match(self::FORBIDDEN_PATTERN, $value)) {
                        $fail('Das Snippet darf keine <html>-, <head>- oder <body>-Tags enthalten.');
                    }
                },
            ],
            'position' => ['required', Rule::in(CustomScript::POSITIONS)],
            'page_mode' => ['required', Rule::in(CustomScript::PAGE_MODES)],
            'page_patterns' => 'nullable|string|max:5000',
            'for_guests' => 'nullable|boolean',
            'for_users' => 'nullable|boolean',
            'for_students' => 'nullable|boolean',
            'restrict_roles' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => ['integer', Rule::exists($rolesTable, 'id')->where('guard_name', 'web')],
            'use_on_chat' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ], [
            'snippet.required' => 'Bitte ein Snippet hinterlegen.',
            'name.required' => 'Bitte einen Namen vergeben.',
        ], [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'page_patterns' => 'Seiten',
            'sort_order' => 'Reihenfolge',
        ]);

        $validator->after(function (Validator $validator) use ($request) {
            if ($request->input('page_mode', CustomScript::PAGE_ALL) !== CustomScript::PAGE_ALL
                && CustomScript::parsePatterns($request->input('page_patterns')) === []) {
                $validator->errors()->add('page_patterns', 'Bitte mindestens eine Seite (Pfadmuster) angeben oder „Alle Seiten“ wählen.');
            }

            if (! $request->boolean('for_users') && ! $request->boolean('for_guests') && ! $request->boolean('for_students')) {
                $validator->errors()->add('for_users', 'Bitte mindestens eine Zielgruppe auswählen.');
            }

            if ($request->boolean('restrict_roles') && empty(array_filter((array) $request->input('roles', [])))) {
                $validator->errors()->add('roles', 'Bei „Auf Rollen begrenzen“ muss mindestens eine Rolle ausgewählt sein.');
            }
        });

        $validated = $validator->validate();

        $script->fill([
            'name' => trim($validated['name']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'active' => $request->boolean('active'),
            'snippet' => trim($validated['snippet']),
            'position' => $validated['position'],
            'page_mode' => $validated['page_mode'],
            'for_guests' => $request->boolean('for_guests'),
            'for_users' => $request->boolean('for_users'),
            'for_students' => $request->boolean('for_students'),
            'restrict_roles' => $request->boolean('restrict_roles'),
            'use_on_chat' => $request->boolean('use_on_chat'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'disabled_reason' => null,
            'updated_by' => $request->user()->id,
        ]);

        // Bereinigt speichern; "/" (Startseite) bleibt als Zeile erhalten
        $script->page_patterns = $this->storablePatterns($validated['page_patterns'] ?? null);

        $script->save();

        $script->roles()->sync($script->restrict_roles ? array_map('intval', (array) ($validated['roles'] ?? [])) : []);
    }

    private function storablePatterns(?string $text): ?string
    {
        $patterns = array_map(fn (string $p) => $p === '' ? '/' : $p, CustomScript::parsePatterns($text));

        return $patterns === [] ? null : implode("\n", $patterns);
    }

    private function log(string $action, CustomScript $script, Request $request, array $extra = []): void
    {
        Log::info("Eigenes Skript {$action}", array_merge([
            'user_id' => $request->user()->id,
            'custom_script_id' => $script->id,
            'name' => $script->name,
            'active' => $script->active,
            'position' => $script->position,
            'page_mode' => $script->page_mode,
            'for_guests' => $script->for_guests,
            'for_users' => $script->for_users,
            'for_students' => $script->for_students,
            'restrict_roles' => $script->restrict_roles,
            'use_on_chat' => $script->use_on_chat,
            'snippet_sha256' => hash('sha256', $script->snippet),
        ], $extra));
    }

    private function ensureEnabled(): void
    {
        abort_unless(CustomScriptRenderer::enabled(), 404);
    }
}
