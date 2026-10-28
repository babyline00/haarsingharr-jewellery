<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /** Find or create by name (case-insensitive). */
    public static function resolve(string $name): self
    {
        $name = trim($name);

        return static::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first()
            ?? static::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name) ?: 'tag',
            ]);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }
}
