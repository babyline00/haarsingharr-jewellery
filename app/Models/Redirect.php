<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_url', 'destination_url', 'status_code', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active'   => 'boolean',
        ];
    }

    public static function normaliseSource(string $url): string
    {
        $url = '/'.ltrim(trim($url), '/');
        $trimmed = rtrim($url, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}
