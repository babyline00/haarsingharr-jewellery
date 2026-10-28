<?php

use App\Mail\PlainNoticeMail;
use App\Models\ActiveSession;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\SecurityEvent;
use App\Models\StoreSetting;
use App\Services\FailedLoginAlert;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Admin system tests (Phase 7)
|--------------------------------------------------------------------------
|
| Settings groups + mail test, roles matrix/clone/search, audit filters +
| export + related-activity, session revocation, failed-login alerts,
| backups, queue failed-job handling and maintenance mode.
|
*/

beforeEach(function () {
    $this->actingAs(createAdmin());
});

// -------------------------------------------------------------------------
// Roles: matrix, clone, search, guards
// -------------------------------------------------------------------------

test('the permission matrix lists modules, actions and roles', function () {
    $role = role('order-manager');
    $permission = Permission::where('module', 'order-manager')->where('action', 'view')->first();
    $role->givePermissionTo([$permission->name]);

    $this->get(route('admin.roles.matrix'))
        ->assertOk()
        ->assertSee('Permission Matrix')
        ->assertSee($role->title)
        ->assertSee('Super Admin')
        ->assertSee(str_replace('-', ' ', 'order-manager'));  // module row header
});

test('the matrix apply endpoint syncs permission changes per role', function () {
    $role = role('order-manager');
    $permission = Permission::where('module', 'order-manager')->where('action', 'view')->first();
    $role->givePermissionTo([$permission->name]);

    // Unchanged submission → "Nothing to change" info flash.
    $this->post(route('admin.roles.matrix.update'), [
        'matrix' => [$role->id => [$permission->id]],
        'matrix_roles' => [$role->id],  // hidden input the form posts alongside the checkboxes
    ])
        ->assertRedirect()
        ->assertSessionHas('info', fn ($message) => str_contains((string) $message, 'Nothing to change'));

    expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();

    // Same submission with the box unticked → revocation + success flash.
    $this->post(route('admin.roles.matrix.update'), [
        'matrix' => [$role->id => []],
        'matrix_roles' => [$role->id],
    ])
        ->assertRedirect()
        ->assertSessionHas('success', fn ($message) => str_contains((string) $message, 'updated'));

    expect($role->fresh()->permissions()->count())->toBe(0)
        ->and(AuditLog::where('action', 'roles.matrix')->where('target_id', $role->id)->exists())->toBeTrue();
});

test('the signed-in super-admin cannot strip their own permissions via the matrix', function () {
    $super = auth()->user(); // acting user is a super-admin
    $permission = Permission::where('module', 'orders')->first() ?? Permission::create([
        'name' => 'orders.view', 'slug' => 'orders.view', 'module' => 'orders', 'action' => 'view', 'guard_name' => 'web',
    ]);
    $super->givePermissionTo([$permission->name]);

    $response = $this->post(route('admin.roles.matrix.update'), ['matrix' => [$super->roles->first()->id => []]])
        ->assertRedirect();

    // Either the locked-column info or the no-change notice is flashed — the
    // permission itself must survive in both cases.
    expect($super->fresh()->hasPermissionTo($permission))->toBeTrue();
});

test('updating your own super-admin role through edit is blocked as well', function () {
    $role = auth()->user()->roles->first(); // super-admin role
    $permission = Permission::create(['name' => 'x-test.perm', 'slug' => 'x-test.perm', 'module' => 'x-test', 'action' => 'perm', 'guard_name' => 'web']);
    $role->givePermissionTo([$permission->name]);

    $this->patch(route('admin.roles.update', $role), ['title' => 'Renamed'])
        ->assertRedirect()
        ->assertSessionHas('error', fn ($message) => str_contains((string) $message, 'cannot remove'));

    expect($role->fresh()->title)->not->toBe('Renamed')
        ->and($role->fresh()->hasPermissionTo($permission))->toBeTrue();
});

test('roles can be cloned with all permissions', function () {
    $role = role('order-manager');
    $role->givePermissionTo(
        Permission::where('module', 'order-manager')->limit(3)->get()->pluck('name')->all(),
    );

    $this->post(route('admin.roles.clone', $role))->assertRedirect();

    $copy = Role::where('slug', 'order-manager-copy')->first();
    expect($copy)->not->toBeNull()
        ->and((bool) $copy->is_system)->toBeFalse()
        ->and($copy->permissions()->count())->toBe($role->permissions()->count());
});

test('the roles index can be searched', function () {
    role('marketing-manager');
    $title = Role::where('slug', 'marketing-manager')->first()->title;

    $this->get(route('admin.roles.index', ['q' => 'marketing']))
        ->assertOk()
        ->assertSee($title);

    $this->get(route('admin.roles.index', ['q' => 'no-match-anything']))
        ->assertOk()
        ->assertDontSee($title);
});

// -------------------------------------------------------------------------
// Audit & security
// -------------------------------------------------------------------------

test('audit logs can be exported as CSV honouring filters', function () {
    AuditLog::create([
        'actor_id' => null,
        'actor_email' => 'system@haarsingharr.pk',
        'action' => 'products.sync',
        'module' => 'products',
        'target_type' => 'App\Models\Product',
        'outcome' => 'success',
        'note' => 'system import',
    ]);

    $response = $this->get(route('admin.audit-logs.export', ['module' => 'products']));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->headers->get('content-disposition'))->toContain('.csv');
});

test('security events can be exported as CSV', function () {
    SecurityEvent::log('login.success', null, 'success', [], 'Test event');

    $response = $this->get(route('admin.security-events.export'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toContain('text/csv');
});

test('audit show links related activity for the same target and actor', function () {
    $actor = auth()->user();
    $target = Product::factory()->create();

    $first = AuditLog::create([
        'actor_id' => $actor->id, 'actor_email' => $actor->email,
        'action' => 'products.update', 'module' => 'products',
        'target_type' => $target::class, 'target_id' => $target->id,
        'outcome' => 'success', 'note' => 'first change',
    ]);

    AuditLog::create([
        'actor_id' => $actor->id, 'actor_email' => $actor->email,
        'action' => 'products.price', 'module' => 'products',
        'target_type' => $target::class, 'target_id' => $target->id,
        'outcome' => 'success', 'note' => 'second change',
    ]);

    $this->get(route('admin.audit-logs.show', $first))
        ->assertOk()
        ->assertSee('Related activity')
        ->assertSee('products.price');
});

test('active sessions can be revoked', function () {
    $staff = createStaffUser();
    $session = ActiveSession::create([
        'user_id' => $staff->id,
        'session_id' => 'sess-'.uniqid(),
        'ip_address' => '127.0.0.1',
        'device' => 'Firefox',
        'last_activity_at' => now(),
    ]);

    $this->post(route('admin.sessions.revoke', $session))
        ->assertRedirect()
        ->assertSessionHas('success', fn ($message) => str_contains((string) $message, 'revoked'));

    expect(ActiveSession::find($session->id))->toBeNull()
        ->and(AuditLog::where('action', 'security.session.revoke')->exists())->toBeTrue();
});

// -------------------------------------------------------------------------
// Failed-login alerts
// -------------------------------------------------------------------------

test('failed-login bursts alert super-admins exactly once per window', function () {
    Mail::fake();
    Cache::flush();

    $email = 'thief@example.com';
    for ($i = 0; $i < 5; $i++) {
        SecurityEvent::log('login.attempt', null, 'failure',
            ['email' => $email], "Failed login attempt for {$email}");
    }

    app(FailedLoginAlert::class)->maybeAlert($email);

    Mail::assertSent(PlainNoticeMail::class, 1);
    expect(SecurityEvent::where('event_type', 'login.alert')->count())->toBe(1);

    // A second call within the same window is a no-op.
    app(FailedLoginAlert::class)->maybeAlert($email);

    Mail::assertSent(PlainNoticeMail::class, 1);
    expect(SecurityEvent::where('event_type', 'login.alert')->count())->toBe(1);
});

test('failed-login alerts are suppressed when the notification setting is off', function () {
    Mail::fake();
    Cache::flush();

    StoreSetting::set('notify_failed_login', false, 'notifications');

    $email = 'quiet@example.com';
    for ($i = 0; $i < 5; $i++) {
        SecurityEvent::log('login.attempt', null, 'failure',
            ['email' => $email], "Failed login attempt for {$email}");
    }

    app(FailedLoginAlert::class)->maybeAlert($email);

    Mail::assertNothingSent();
    expect(SecurityEvent::where('event_type', 'login.alert')->count())->toBe(0);
});

test('a burst below the threshold never alerts', function () {
    Mail::fake();
    Cache::flush();

    $email = 'calm@example.com';
    for ($i = 0; $i < 4; $i++) {
        SecurityEvent::log('login.attempt', null, 'failure',
            ['email' => $email], "Failed login attempt for {$email}");
    }

    app(FailedLoginAlert::class)->maybeAlert($email);

    Mail::assertNothingSent();
});

test('the real login route records failures and never 500s', function () {
    Mail::fake();
    Cache::flush();

    // Log out first — /login is behind the guest middleware and would
    // otherwise redirect without reaching LoginController.
    auth()->logout();
    session()->flush();

    for ($i = 0; $i < 3; $i++) {
        $this->followingRedirects()->from('/login')->post(route('login.attempt'), [
            'email' => 'ghost@example.com',
            'password' => 'wrong-password',
        ])->assertOk();
    }

    expect(SecurityEvent::where('event_type', 'login.attempt')->where('outcome', 'failure')->count())->toBe(3);
    // Below the burst threshold: no alert event, no mail.
    expect(SecurityEvent::where('event_type', 'login.alert')->count())->toBe(0);
    Mail::assertNothingSent();
});

// -------------------------------------------------------------------------
// Settings groups + mail test
// -------------------------------------------------------------------------

test('checkout rules and new settings groups persist per module', function () {
    $this->post(route('admin.settings.update'), [
        'settings' => [
            'checkout' => ['cod_max_order_value' => '200000', 'cod_note' => 'Verify cash with the courier.'],
            'email' => ['email_from_name' => 'Haarsingharr', 'email_from_address' => 'shop@example.com', 'email_admin_address' => 'staff@example.com'],
            'notifications' => ['notify_new_order' => true, 'notify_low_stock' => false, 'notify_failed_login' => true],
            'legal' => ['legal_terms_url' => '/pages/terms-updated'],
            'system' => ['audit_retention_days' => '180'],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    expect(StoreSetting::where('key', 'cod_max_order_value')->first()?->value)->toEqual(200000.0)
        ->and((bool) StoreSetting::where('key', 'notify_low_stock')->first()?->value)->toBeFalse()
        ->and((bool) StoreSetting::where('key', 'notify_new_order')->first()?->value)->toBeTrue()
        ->and((int) StoreSetting::where('key', 'audit_retention_days')->first()?->value)->toBe(180)
        ->and(StoreSetting::where('key', 'legal_terms_url')->first()?->value)->toBe('/pages/terms-updated');
});

test('staff can trigger an SMTP test email', function () {
    Mail::fake();

    $this->post(route('admin.settings.email-test', ['email' => 'staff@example.com']))
        ->assertRedirect()
        ->assertSessionHas('success', fn ($message) => str_contains((string) $message, 'staff@example.com'));

    Mail::assertSent(PlainNoticeMail::class, 1);
});

test('an invalid test email is rejected by validation', function () {
    Mail::fake();

    $this->post(route('admin.settings.email-test', ['email' => 'not-an-email']))
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

// -------------------------------------------------------------------------
// System page: health, backups, migrations, queue, maintenance
// -------------------------------------------------------------------------

test('the system panel renders health, backups, migrations, queue and schedule', function () {
    $this->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('Health checks')
        ->assertSee('Backups')
        ->assertSee('Migrations')
        ->assertSee('Failed queue jobs')
        ->assertSee('Maintenance mode')
        ->assertSee('Schedule')
        ->assertSee('PHP version')
        ->assertSee('haarsingharr:weekly-report');
});

test('backup creation reports the missing zip extension gracefully', function () {
    if (class_exists('ZipArchive')) {
        $this->markTestSkipped('ext-zip installed on this host.');
    }

    $this->post(route('admin.system.backup.create'))
        ->assertRedirect()
        ->assertSessionHas('error', fn ($message) => str_contains((string) $message, 'zip extension'));

    expect(app()->isDownForMaintenance())->toBeFalse();
});

test('the empty backups tab renders without breaking', function () {
    $this->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('No backups yet');
});

test('failed queue jobs can be retried, deleted and flushed', function () {
    $uuid = (string) Str::uuid();

    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'sync',
        'queue' => 'failures',
        'payload' => json_encode(['displayName' => 'TestFailure', 'job' => 'test']),
        'exception' => 'intentional test exception',
        'failed_at' => now(),
    ]);
    $id = DB::table('failed_jobs')->where('uuid', $uuid)->value('id');

    // A retry dispatch of a bogus payload is caught and flashed, never a 500.
    $this->post(route('admin.system.failed-jobs.retry', $id))->assertRedirect();

    $this->post(route('admin.system.failed-jobs.delete', $id))
        ->assertRedirect()
        ->assertSessionHas('success', fn ($message) => str_contains((string) $message, 'deleted'));

    expect(DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();

    // Flush on an empty list is still fine.
    $this->post(route('admin.system.failed-jobs.flush'))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('maintenance mode can be toggled down and back up', function () {
    try {
        $this->post(route('admin.system.maintenance', ['confirm' => 'MAINTENANCE']))
            ->assertRedirect();

        expect(file_exists(storage_path('framework/down')))->toBeTrue()
            ->and(app()->isDownForMaintenance())->toBeTrue();

        // The success flash carries the bypass URL; use it to reach the app
        // while the site is down, just like an admin would in a browser.
        $flash = (string) session('success');
        expect($flash)->toContain('Bypass URL');
        preg_match('#/([A-Za-z0-9]{24})$#', $flash, $m);
        expect($m[1] ?? null)->not->toBeNull();

        // Visiting the secret path issues a maintenance bypass cookie; send it
        // back explicitly — the test client does not persist response cookies.
        $grant = $this->post('/'.$m[1])->assertRedirect();
        $cookie = $grant->getCookie('laravel_maintenance', false)?->getValue();
        expect($cookie)->not->toBeNull();

        $this->withUnencryptedCookies(['laravel_maintenance' => $cookie])
            ->post(route('admin.system.maintenance'), ['confirm' => 'MAINTENANCE'])
            ->assertRedirect();

        expect(file_exists(storage_path('framework/down')))->toBeFalse()
            ->and(app()->isDownForMaintenance())->toBeFalse();
    } finally {
        if (file_exists(storage_path('framework/down'))) {
            Artisan::call('up');
        }
    }
});

test('maintenance toggle requires the typed confirmation', function () {
    $this->post(route('admin.system.maintenance', ['confirm' => 'WRONG']))
        ->assertSessionHasErrors('confirm');

    expect(file_exists(storage_path('framework/down')))->toBeFalse();
});

// -------------------------------------------------------------------------
// Audit retention prune
// -------------------------------------------------------------------------

test('the prune command removes only audit logs past the retention window', function () {
    StoreSetting::set('audit_retention_days', 180, 'system');

    $oldId = DB::table('audit_logs')->insertGetId([
        'actor_id' => null, 'action' => 'old.event', 'module' => 'system', 'outcome' => 'success',
        'uuid' => (string) Str::uuid(),
        'created_at' => now()->subDays(200)->toDateTimeString(),
        'updated_at' => now()->subDays(200)->toDateTimeString(),
    ]);
    $recentId = DB::table('audit_logs')->insertGetId([
        'actor_id' => null, 'action' => 'recent.event', 'module' => 'system', 'outcome' => 'success',
        'uuid' => (string) Str::uuid(),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    Artisan::call('haarsingharr:prune-audit');

    // Prune is a hard delete on stale rows; audit retention is 180 days here.
    expect(DB::table('audit_logs')->where('id', $oldId)->exists())->toBeFalse()
        ->and(DB::table('audit_logs')->where('id', $recentId)->exists())->toBeTrue();
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

if (! function_exists('role')) {
    /**
     * Get or create a role with its own module of scaffold permissions
     * (module = slug, names "<slug>.<action>").
     */
    function role(string $slug): Role
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug, 'name' => $slug],
            ['title' => ucfirst(str_replace('-', ' ', $slug)), 'guard_name' => 'web'],
        );

        foreach (['view', 'create', 'update', 'delete', 'export'] as $action) {
            Permission::firstOrCreate([
                'name' => "{$slug}.{$action}",
                'slug' => "{$slug}.{$action}",
                'module' => $slug,
                'action' => $action,
                'guard_name' => 'web',
            ]);
        }

        return $role;
    }
}

if (! function_exists('createStaffUser')) {
    function createStaffUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'type' => 'staff', 'status' => 'active',
            'first_name' => 'Revoked', 'last_name' => 'Staff',
        ]);
    }
}
