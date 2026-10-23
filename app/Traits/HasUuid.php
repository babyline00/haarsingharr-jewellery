<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getAttribute('uuid')) {
                $model->setAttribute('uuid', (string) Str::orderedUuid());
            }
        });
    }

    public function getRouteKey(): string
    {
        // Always generate URLs from the primary key. Route model binding uses
        // getRouteKeyName() (which stays "id"), so deriving the URL from the
        // slug instead would produce links that fail to resolve. Storefront
        // routes request slugs explicitly via "{model:slug}" bindings.
        return (string) parent::getRouteKey();
    }
}
