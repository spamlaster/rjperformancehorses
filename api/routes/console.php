<?php

use App\Models\User;
use Database\Seeders\DefaultLessonTypesSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;

// There is no public registration; admins are provisioned on the server.
Artisan::command('admin:create {email} {--name=} {--role=admin} {--password=}', function (string $email) {
    $input = ['email' => $email, 'role' => $this->option('role')];
    $validator = Validator::make($input, [
        'email' => ['required', 'email', 'unique:users,email'],
        'role' => ['required', 'in:admin,staff,instructor'],
    ]);
    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }

    $secret = $this->option('password') ?? password('Password', required: true, validate: fn ($v) => strlen($v) < 12 ? 'Use at least 12 characters.' : null);
    User::create([...$input, 'name' => $this->option('name'), 'password' => $secret, 'is_active' => true]);
    $this->info("Admin {$email} created.");
})->purpose('Create an admin login');

Artisan::command('app:fresh {--email=admin@admin.com} {--force}', function (): int {
    $db = DB::connection();
    if (! app()->environment('local') || $db->getDriverName() !== 'mysql'
        || ! in_array($db->getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
        $this->error('Fresh setup is only available for a local MySQL database.');

        return 1;
    }
    $admin = $db->table('users')->where('email', $this->option('email'))
        ->where('role', 'admin')->where('is_active', true)->first();
    if (! $admin) {
        $this->error('The active admin account to preserve was not found. No data was changed.');

        return 1;
    }
    if (! $this->option('force') && ! $this->confirm('Clear all local business data and preserve only '.$admin->email.'?')) {
        return 1;
    }
    $attributes = (array) $admin;
    if ($this->call('migrate:fresh', ['--force' => true, '--no-interaction' => true]) !== 0) {
        return 1;
    }
    $db->table('users')->insert($attributes);
    if ($this->call('db:seed', ['--class' => DefaultLessonTypesSeeder::class, '--force' => true, '--no-interaction' => true]) !== 0) {
        return 1;
    }
    $this->info('Fresh setup ready. Preserved '.$admin->email.' with the same password. Standard lessons created; no demo customers, horses, or bookings loaded.');

    return 0;
})->purpose('Clear local data for a fresh setup while preserving an admin login');
