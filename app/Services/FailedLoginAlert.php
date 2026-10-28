<?php

namespace App\Services;

use App\Mail\PlainNoticeMail;
use App\Models\SecurityEvent;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Alerts super-admins on failed-login bursts (>= 5 failed sign-ins for the
 * same email within 15 minutes). One alert per email per window.
 */
class FailedLoginAlert
{
    private const WINDOW_SECONDS = 15 * 60;

    private const THRESHOLD = 5;

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Called right after a failed login attempt is recorded. Fires an alert
     * security event + a queued email when the burst threshold is crossed.
     */
    public function maybeAlert(string $email): void
    {
        $recentFailures = SecurityEvent::query()
            ->whereIn('event_type', ['login.attempt', 'login.suspended'])
            ->whereIn('outcome', ['failure', 'blocked'])
            ->where('email', $email)
            ->where('created_at', '>=', now()->subSeconds(self::WINDOW_SECONDS))
            ->count();

        if ($recentFailures < self::THRESHOLD) {
            return;
        }

        // One alert per email per window (cache-flag guard).
        $key = 'login-alert:'.sha1(strtolower($email));
        if (! Cache::add($key, now()->timestamp, self::WINDOW_SECONDS)) {
            return;
        }

        if (! $this->notificationsEnabled()) {
            return;
        }

        SecurityEvent::log(
            'login.alert',
            null,
            'warning',
            ['email' => $email, 'failures_in_window' => $recentFailures],
            "Failed-login burst detected for {$email}",
        );

        $recipients = User::where('type', 'admin')->get()->filter(fn ($u) => $u->isSuperAdmin());

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new PlainNoticeMail(
                '[Security] Failed-login alert — '.now()->format('M j, Y H:i'),
                sprintf(
                    "%d failed sign-in attempts were recorded for \"%s\" within 15 minutes.\n\n".
                    "Last attempt at %s from %s.\n\n".
                    "Review Security Events in the admin panel if this was not expected.",
                    $recentFailures,
                    $email,
                    now()->toDateTimeString(),
                    request()?->ip() ?? 'unknown IP',
                ),
            ));
        }
    }

    private function notificationsEnabled(): bool
    {
        $setting = StoreSetting::where('key', 'notify_failed_login')->first();

        return $setting === null || filter_var($setting->value, FILTER_VALIDATE_BOOL);
    }
}
