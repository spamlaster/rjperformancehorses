<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every login (admins today; riders and SSO users later) is a row in users.
     * Replaces Supabase auth.users + admin_profiles.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->dateTime('email_verified_at')->nullable();
            // Nullable so SSO-only accounts can exist without a password.
            $table->string('password')->nullable();
            // NULL = not staff. Any staff role grants admin access (as admin_profiles did).
            $table->string('role', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->datetimes();
        });
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IS NULL OR role IN ('admin', 'staff', 'instructor'))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
