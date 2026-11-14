<?php

namespace App\Http\Controllers\API\V1;

use App\Model\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Modul „Links“: die für den Nutzer sichtbaren Links.
 *
 * @group Links
 */
class LinkController extends ApiController
{
    /**
     * Sichtbare Links in Anzeigereihenfolge.
     *
     * `url` ist immer absolut. `type`: internal (Seite dieses Boards), external, mail, phone.
     * `icon` ist die Font-Awesome-Klasse, `icon_key` der Name ohne Präfix (z. B. „utensils“).
     * `pinned` = auf der Startseite anzeigen. `meta.categories` nennt die Kategorien in Anzeigereihenfolge
     * (`null` = ohne Kategorie).
     */
    public function index(Request $request): JsonResponse
    {
        $links = Link::query()->visibleTo($request->user())->ordered()->get();

        return response()->json([
            'data' => $links->map(fn (Link $link) => self::present($link))->values(),
            'meta' => [
                'categories' => Link::grouped($links)->keys()
                    ->map(fn ($category) => $category === '' ? null : $category)->values(),
            ],
        ]);
    }

    public static function present(Link $link): array
    {
        return [
            'id' => $link->id,
            'title' => $link->title,
            'description' => $link->description,
            'url' => $link->href(),
            'type' => $link->type(),
            'host' => $link->displayHost(),
            'category' => $link->category,
            'icon' => $link->iconClass(),
            'icon_key' => $link->iconKey(),
            'pinned' => $link->pinned,
            'position' => $link->position,
            'updated_at' => $link->updated_at?->toIso8601String(),
        ];
    }
}
