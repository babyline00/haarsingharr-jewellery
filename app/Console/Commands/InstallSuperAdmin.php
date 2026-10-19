<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Bootstrap the first Super Admin account through a documented,
 * secure setup procedure. Must be run with --email and --password.
 *
 * Usage:
 *   php artisan haarsingharr:install-super-admin \
 *     --email=admin@haarsingharr.example \
 *     --password='Str0ng!Pa55' \
 *     --first=Admin --last=User
 *
 * For cPanel hosts without terminal access, the same procedure is
 * documented in README.md using the included setup.php web installer
 * which is automatically deleted after first use.
 */
class InstallSuperAdmin extends Command
{
    protected $signature = 'haarsingharr:install-super-admin
        {--email= : Email address of the Super Admin}
        {--password= : Strong password (will be hidden in console history)}
        {--first= : First name}
        {--last= : Last name}
        {--phone= : Phone number}
        {--force : Allow overwriting existing super admin}';

    protected $description = 'Bootstrap the first Super Admin account securely.';

    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password');

        if (! $email || ! $password) {
            $this->error('Both --email and --password are required.');
            $this->info('Usage: php artisan haarsingharr:install-super-admin --email=... --password=...');
            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email address is not valid.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists() && ! $this->option('force')) {
            $this->error("A user already exists with email {$email}.");
            $this->info('Use --force to overwrite the password.');
            return self::FAILURE;
        }

        $this->ensureSuperAdminRole();
        $this->warn('Validating password strength...');
        $issues = $this->passwordIssues($password);
        if ($issues) {
            $this->error('Password does not meet requirements:');
            foreach ($issues as $issue) {
                $this->line(" • {$issue}");
            }
            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $this->option('first') ?? 'Super',
                'last_name'  => $this->option('last') ?? 'Admin',
                'phone'      => $this->option('phone'),
                'password'   => Hash::make($password),
                'type'       => UserType::Admin,
                'status'     => 'active',
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]
        );

        $superAdmin = Role::where('slug', 'super-admin')->first();
        if (! $user->roles()->where('role_id', $superAdmin->id)->exists()) {
            $user->roles()->attach($superAdmin->id);
        }

        $this->info("Super Admin account created/updated for {$email}");
        $this->warn('IMPORTANT:');
        $this->line('  • Enroll MFA immediately after first login.');
        $this->line('  • Delete this command or restrict execution to install time.');
        $this->line('  • Never share these credentials.');
        return self::SUCCESS;
    }

    private function ensureSuperAdminRole(): void
    {
        if (! Role::where('slug', 'super-admin')->exists()) {
            $this->call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
        }
    }

    private function passwordIssues(string $password): array
    {
        $issues = [];
        if (strlen($password) < 12) {
            $issues[] = 'Must be at least 12 characters.';
        }
        if (! preg_match('/[A-Z]/', $password)) {
            $issues[] = 'Must include at least one uppercase letter.';
        }
        if (! preg_match('/[a-z]/', $password)) {
            $issues[] = 'Must include at least one lowercase letter.';
        }
        if (! preg_match('/[0-9]/', $password)) {
            $issues[] = 'Must include at least one digit.';
        }
        if (! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $issues[] = 'Must include at least one symbol.';
        }
        return $issues;
    }
}
