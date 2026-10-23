<?php

namespace App\Models;

use App\Enums\UserType;
use App\Enums\UserStatus;
use App\Traits\BelongsToOrganisation;
use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, HasUuid, Notifiable, SoftDeletes, LogsActivity;

    protected $fillable = [
        'first_name', 'last_name', 'name', 'email', 'phone_country', 'phone',
        'username', 'password', 'avatar', 'type', 'status',
        'two_factor_secret', 'two_factor_recovery_codes',
        'marketing_consent', 'last_login_ip', 'internal_notes',
    ];

    protected $hidden = [
        'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'type'                      => UserType::class,
            'status'                    => UserStatus::class,
            'email_verified_at'         => 'datetime',
            'phone_verified_at'         => 'datetime',
            'password_changed_at'       => 'datetime',
            'last_login_at'             => 'datetime',
            'two_factor_confirmed_at'   => 'datetime',
            'password'                  => 'hashed',
            'marketing_consent'         => 'boolean',
            'marketing_consent_at'      => 'datetime',
            'data_export_requested_at'  => 'datetime',
            'data_deleted_at'           => 'datetime',
            'consent_log'               => 'array',
        ];
    }

    // --------------------------------------------------------------------------
    // Boot
    // --------------------------------------------------------------------------
    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->first_name ??= '';
            $user->last_name ??= '';
            // Customers get the default "customer" role assigned at creation.
        });

        static::saving(function (self $user) {
            // Sync display name from components if not explicitly set
            $user->name = trim(($user->first_name).' '.($user->last_name));
        });
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeStaff($query)
    {
        return $query->whereIn('type', [UserType::Staff, UserType::Admin]);
    }

    public function scopeCustomers($query)
    {
        return $query->where('type', UserType::Customer);
    }

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::Active);
    }

    public function scopeOrderedByActivity($query)
    {
        return $query->orderByDesc('last_login_at');
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultShippingAddress()
    {
        return $this->addresses()->where('is_default_shipping', true)->first()
            ?? $this->addresses()->first();
    }

    public function defaultBillingAddress()
    {
        return $this->addresses()->where('is_default_billing', true)->first()
            ?? $this->defaultShippingAddress();
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class)->latestOfMany();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function giftClaims(): HasMany
    {
        return $this->hasMany(GiftClaim::class);
    }

    public function giveawayEntries(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function isStaff(): bool
    {
        return in_array($this->type, [UserType::Staff, UserType::Admin], true);
    }

    public function isCustomer(): bool
    {
        return $this->type === UserType::Customer;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function requiresMfa(): bool
    {
        if (! config('app.features.mfa_required')) {
            return false;
        }
        return $this->isStaff()
            && $this->roles()->whereIn('slug', ['super-admin', 'store-admin'])->exists();
    }

    public function hasMfaEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function markEmailVerified(): void
    {
        $this->forceFill([
            'email_verified_at' => $this->email_verified_at ?? now(),
        ])->save();
    }

    public function touchLastLogin(string $ip): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();
    }

    public function can($ability, $arguments = []): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return parent::can($ability, $arguments);
    }
}
