<?php

namespace App\Mcp\Tools;

use App\Mcp\ReadOnlyApiGateway;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Lesendes Werkzeug, das genau eine GET-Route der App-API abbildet.
 *
 * `$path` enthält Platzhalter für Pfadparameter (`{post}`); alle weiteren Parameter gehen als Query-String
 * an die Route. Parameter: name => [type (string|integer|boolean), description, in (path|query), required,
 * enum, min, max, pattern].
 */
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class ApiEndpointTool extends Tool
{
    private const MAX_CHARS = 100000;

    /** @param  array<string, array<string, mixed>>  $params */
    public function __construct(
        string $name,
        string $description,
        private readonly string $path,
        private readonly array $params = [],
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->title = $name;
    }

    public function schema(JsonSchema $schema): array
    {
        $properties = [];

        foreach ($this->params as $name => $spec) {
            $type = match ($spec['type']) {
                'integer' => $schema->integer(),
                'boolean' => $schema->boolean(),
                default => $schema->string(),
            };
            $type = $type->description($spec['description']);
            if (isset($spec['enum'])) {
                $type = $type->enum($spec['enum']);
            }
            if (isset($spec['min'])) {
                $type = $type->min($spec['min']);
            }
            if (isset($spec['max'])) {
                $type = $type->max($spec['max']);
            }
            $properties[$name] = $this->isRequired($spec) ? $type->required() : $type;
        }

        return $properties;
    }

    public function handle(Request $request, ReadOnlyApiGateway $gateway): Response
    {
        $user = $request->user('sanctum') ?? $request->user();
        if (! $user) {
            return Response::error('Nicht angemeldet.');
        }

        $arguments = $request->all();
        if ($unknown = array_diff(array_keys($arguments), array_keys($this->params))) {
            return Response::error('Unbekannte Parameter: '.implode(', ', $unknown));
        }

        $validator = Validator::make($arguments, $this->rules());
        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $uri = $this->path;
        $query = [];
        foreach ($this->params as $name => $spec) {
            if (! array_key_exists($name, $arguments) || $arguments[$name] === null) {
                continue;
            }
            $value = $spec['type'] === 'boolean' ? ((bool) $arguments[$name] ? '1' : '0') : (string) $arguments[$name];
            if (($spec['in'] ?? 'query') === 'path') {
                $uri = str_replace('{'.$name.'}', rawurlencode($value), $uri);
            } else {
                $query[$name] = $value;
            }
        }

        $result = $gateway->get($user, $uri, $query);

        if ($result['status'] >= 400) {
            $message = json_decode($result['body'], true)['message'] ?? 'Die Anfrage konnte nicht ausgeführt werden.';

            return Response::error("Fehler {$result['status']}: {$message}");
        }

        $body = $result['body'];
        if (mb_strlen($body) > self::MAX_CHARS) {
            $body = mb_substr($body, 0, self::MAX_CHARS)."\n[Antwort gekürzt – bitte mit Filtern oder Pagination eingrenzen.]";
        }

        return Response::text($body);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $rules = [];

        foreach ($this->params as $name => $spec) {
            $rule = [$this->isRequired($spec) ? 'required' : 'nullable'];
            $rule[] = $spec['type'] === 'string' ? 'string' : $spec['type'];
            $rule[] = $spec['type'] === 'string' ? 'max:200' : null;
            if (isset($spec['min'])) {
                $rule[] = 'min:'.$spec['min'];
            }
            if (isset($spec['max']) && $spec['type'] === 'integer') {
                $rule[] = 'max:'.$spec['max'];
            }
            if (isset($spec['enum'])) {
                $rule[] = Rule::in($spec['enum']);
            }
            if (isset($spec['pattern'])) {
                $rule[] = 'regex:'.$spec['pattern'];
            }
            $rules[$name] = array_values(array_filter($rule));
        }

        return $rules;
    }

    /** @param  array<string, mixed>  $spec */
    private function isRequired(array $spec): bool
    {
        return ($spec['in'] ?? 'query') === 'path' || ! empty($spec['required']);
    }
}
