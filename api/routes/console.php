<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
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
