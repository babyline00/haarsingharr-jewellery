<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Record an audit entry. If the actor is null, the current authenticated
     * user (if any) is used. Sensitive properties (password, tokens) are
     * stripped automatically.
     */
    public function record(string $action, Model|string|null $target = null,
        ?array $previous = null, ?array $after = null, ?string $module = null): ?AuditLog
    {
        if (! $target) {
            return null;
        }

        $actor = $this->currentActor();
        $targetType = is_string($target) ? $target : $target::class;
        $targetId = is_string($target) ? null : $target->getKey();

        return AuditLog::create([
            'actor_id'     => $actor?->id,
            'actor_email'  => $actor?->email,
            'action'       => $action,
            'module'       => $module ?? $this->guessModule($targetType),
            'target_type'  => $targetType,
            'target_id'    => $targetId,
            'previous'      => $this->sanitize($previous),
            'after'         => $this->sanitize($after),
            'properties'    => ['action' => $action],
            'ip_address'    => request()?->ip(),
            'user_agent'    => request()?->userAgent(),
            'outcome'       => 'success',
            'note'          => $this->buildNote($action, $targetType),
        ]);
    }

    public function recordSecurity(string $eventType, string $outcome = 'success',
        ?User $user = null, array $context = [], ?string $description = null): SecurityEvent
    {
        return SecurityEvent::log($eventType, $user, $outcome, $context, $description);
    }

    /**
     * Strip sensitive fields from audit payloads.
     */
    private function sanitize(?array $payload): ?array
    {
        if (! $payload) {
            return null;
        }
        $forbidden = config('payments.security.never_store_fields', [
            'password', 'password_confirmation', 'current_password',
            'card_number', 'cvv', 'pin', 'token', 'secret', 'secret_key',
            'two_factor_secret', 'two_factor_recovery_codes',
        ]);

        array_walk_recursive($payload, function (&$value, $key) use ($forbidden) {
            foreach ($forbidden as $word) {
                if (stripos($key, $word) !== false) {
                    $value = '[redacted]';
                    return;
                }
            }
        });
        return $payload;
    }

    private function currentActor(): ?User
    {
        if (app()->runningInConsole()) {
            return null;
        }
        if (auth()->check()) {
            return auth()->user();
        }
        return null;
    }

    private function guessModule(string $targetType): ?string
    {
        if (! str_contains($targetType, '\\')) {
            return null;
        }
        $shortName = class_basename($targetType);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName));
    }

    private function buildNote(string $action, string $targetType): string
    {
        return $action.' '.class_basename($targetType);
    }
}
