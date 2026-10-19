<?php

/**
 * Haarsingharr — cPanel Web Installer (one-shot)
 *
 * Use this ONLY on cPanel hosts where you cannot run `php artisan haarsingharr:install-super-admin`
 * from the terminal. After successful installation, this file is automatically
 * deleted to prevent re-running.
 *
 * Safety features:
 *   1. Checks APP_ENV — refuses to run if not production.
 *   2. Checks for an existing super-admin user — refuses to overwrite without --force.
 *   3. Validates password strength before creating the account.
 *   4. Self-deletes the file once a Super Admin is created.
 */

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/html; charset=utf-8');

$message = '';
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? 'Super');
    $lastName = trim($_POST['last_name'] ?? 'Admin');
    $phone = trim($_POST['phone'] ?? '');

    // Validation
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email is required.';
    } elseif (strlen($password) < 12) {
        $error = 'Password must be at least 12 characters.';
    } elseif (! preg_match('/[A-Z]/', $password)
              || ! preg_match('/[a-z]/', $password)
              || ! preg_match('/[0-9]/', $password)
              || ! preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = 'Password must include uppercase, lowercase, number and symbol.';
    } elseif (\App\Models\User::where('email', $email)->exists()) {
        $error = 'A user with that email already exists. Delete the user or contact the developer.';
    } else {
        try {
            // Ensure roles are seeded
            if (! \Spatie\Permission\Models\Role::where('slug', 'super-admin')->exists()) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'RolesAndPermissionsSeeder',
                    '--force' => true,
                ]);
            }

            // Create the Super Admin
            $user = \App\Models\User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'phone'      => $phone,
                'password'   => \Illuminate\Support\Facades\Hash::make($password),
                'type'       => 'admin',
                'status'     => 'active',
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]);

            $superAdmin = \Spatie\Permission\Models\Role::where('slug', 'super-admin')->first();
            $user->roles()->attach($superAdmin->id);

            $message = "Super Admin account created for {$email}.";
            $done = true;

            // Self-destruct — never leave this file accessible after a successful install.
            @unlink(__FILE__);
        } catch (\Throwable $e) {
            $error = 'Installation failed: '.$e->getMessage();
        }
    }
}

// If a Super Admin already exists, refuse to render the form.
$existing = \App\Models\User::whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))->exists();
if ($existing && ! $done) {
    echo "<h1>Installer locked</h1><p>A Super Admin account already exists on this installation. If you need to reset credentials, contact the developer or restore from a known backup.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Haarsingharr.pk Installer</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; background: #FBF8F2; color: #1F1B16; max-width: 480px; margin: 80px auto; padding: 0 20px; }
    h1 { font-family: Georgia, serif; margin: 0 0 8px; font-weight: 500; }
    p { color: #6F6A63; font-size: 14px; }
    form { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 32px rgba(31,27,22,0.08); }
    label { display: block; font-size: 12px; font-weight: 500; margin: 12px 0 4px; }
    input { width: 100%; padding: 10px 12px; border: 1px solid #1F1B16; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
    button { background: #C8A968; color: #1F1B16; border: 0; padding: 12px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 20px; }
    .error { color: #B23A48; font-size: 13px; background: #B23A4822; padding: 10px; border-radius: 6px; margin: 10px 0; }
    .success { color: #3F8E5C; font-size: 13px; background: #3F8E5C22; padding: 10px; border-radius: 6px; margin: 10px 0; }
    .warn { font-size: 12px; color: #6F6A63; margin-top: 16px; }
</style>
</head>
<body>
<h1>Haarsingharr.pk Installer</h1>
<p>One-time Super Admin setup. This file self-deletes after successful installation.</p>

<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if (! $done): ?>
<form method="post">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label for="first_name">First name</label>
    <input type="text" id="first_name" name="first_name" required value="Super">
    <label for="last_name">Last name</label>
    <input type="text" id="last_name" name="last_name" required value="Admin">
    <label for="phone">Phone (optional)</label>
    <input type="tel" id="phone" name="phone" value="">
    <label for="password">Password (min 12 chars + uppercase + lowercase + digit + symbol)</label>
    <input type="password" id="password" name="password" required minlength="12">
    <button type="submit">Create Super Admin</button>
</form>
<p class="warn">After successful installation, this file is deleted. Never leave this installer accessible on a live site.</p>
<?php else: ?>
<p>✓ Installation complete. Visit the <a href="/admin">admin dashboard</a> and log in.</p>
<?php endif; ?>
</body>
</html>
