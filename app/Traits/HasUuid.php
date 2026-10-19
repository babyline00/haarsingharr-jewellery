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
        if (request()?->route()?->bindingFieldFor('id')) {
            return $this->getKey();
        }
        if (in_array('slug', $this->fillable, true) && $this->slug) {
            return $this->slug;
        }
        return parent::getRouteKey();
    }
}
