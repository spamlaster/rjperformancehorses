<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account holders: parents, guardians, or adult riders booking for themselves.
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            // Replaces the Supabase auth_user_id link.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            // Case-insensitive (table collation): guest booking dedupes Jane@x.com and jane@x.com.
            $table->string('email', 255)->index();
            $table->string('phone', 20)->nullable();
            $table->datetimes();
            $table->dateTime('deleted_at')->nullable()->index();
        });
        DB::statement("ALTER TABLE customers ADD CONSTRAINT email_not_empty CHECK (email <> '')");

        // People who actually ride lessons.
        Schema::create('riders', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('customer_id', 36)->nullable()->index();
            // Reserved for per-rider logins (Phase 2).
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            // Binary collation: guest booking matches rider names exactly, as Postgres did.
            $table->string('first_name', 100)->collation('utf8mb4_bin');
            $table->string('last_name', 100)->collation('utf8mb4_bin');
            $table->string('email', 255)->nullable()->index();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('riding_experience', 20)->nullable()->default('beginner');
            $table->text('notes')->nullable();
            $table->datetimes();
            $table->dateTime('deleted_at')->nullable()->index();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
        Schema::dropIfExists('customers');
    }
};
