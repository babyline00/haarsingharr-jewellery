<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationMenu extends Model
{
    use HasFactory;

    public const LOCATIONS = ['main', 'footer', 'mobile'];

    protected $fillable = ['name', 'slug', 'location', 'items'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    /** The live menu for a storefront location, first one wins. */
    public static function forLocation(string $location): ?self
    {
        return static::query()
            ->where('location', $location)
            ->orderBy('id')
            ->first();
    }

    /**
     * Top-level items with resolved storefront URLs and visibility applied.
     * Stored item shape: [label, type (page|category|collection|custom), link, is_visible, children].
     */
    public function visibleItems(): array
    {
        return collect($this->items ?? [])
            ->filter(fn (array $item) => $this->isVisible($item))
            ->map(fn (array $item) => [
                'label'    => $item['label'] ?? '',
                'url'      => self::resolveUrl($item),
                'type'     => $item['type'] ?? 'custom',
                'children' => collect($item['children'] ?? [])
                    ->filter(fn (array $child) => $this->isVisible($child))
                    ->map(fn (array $child) => [
                        'label' => $child['label'] ?? '',
                        'url'   => self::resolveUrl($child),
                        'type'  => $child['type'] ?? 'custom',
                    ])->all(),
            ])->all();
    }

    /** Resolve a menu item (page/category/collection types) to its live URL. */
    public static function resolveUrl(array $item): string
    {
        $link = (string) ($item['link'] ?? '');

        return match ($item['type'] ?? 'custom') {
            'page'       => $link !== '' ? route('pages.show', $link) : '#',
            'category'   => ($category = \App\Models\Category::find($link))
                ? route('categories.show', $category) : '#',
            'collection' => ($collection = \App\Models\Collection::find($link))
                ? route('collections.show', $collection) : '#',
            default      => $link !== '' ? $link : '#',
        };
    }

    private function isVisible(array $item): bool
    {
        return ($item['is_visible'] ?? true) && ! empty($item['label']);
    }
}
