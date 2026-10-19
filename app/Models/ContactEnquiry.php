<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEnquiry extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'type', 'status', 'assigned_to', 'internal_notes',
        'ip_address',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen(Builder $q)
    {
        return $q->whereIn('status', ['new', 'open']);
    }
}
