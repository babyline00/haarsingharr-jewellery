<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['key', 'value', 'data', 'module', 'is_secret'];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'data'      => 'array',
        ];
    }

    public static function get(string $key, $default = null)
    {
        $cacheKey = 'setting:'.$key;
        return cache()->remember($cacheKey, 300, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, $value, ?string $module = null, bool $secret = false): self
    {
        $setting = self::updateOrCreate(['key' => $key], [
            'value' => is_array($value) ? null : $value,
            'data' => is_array($value) ? $value : null,
            'module' => $module,
            'is_secret' => $secret,
        ]);
        cache()->forget('setting:'.$key);
        return $setting;
    }
}
