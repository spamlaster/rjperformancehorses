<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waivers', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('title', 255);
            $table->integer('version')->default(1)->unique();
            $table->text('content');
            $table->date('effective_date');
            $table->boolean('active')->default(true);
            $table->datetimes();
        });

        // Immutable record of a waiver accepted at a specific time.
        Schema::create('waiver_acceptances', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('waiver_id', 36);
            $table->string('customer_id', 36)->index();
            $table->string('rider_id', 36)->index();
            $table->string('booking_id', 36)->nullable()->index();
            $table->string('signer_name', 255);
            $table->string('signer_email', 255)->nullable();
            $table->string('relationship_to_rider', 100)->nullable();
            $table->dateTime('accepted_at')->useCurrent();
            $table->text('signature_data')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->foreign('waiver_id')->references('id')->on('waivers');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('rider_id')->references('id')->on('riders');
            $table->foreign('booking_id')->references('id')->on('bookings');
        });
        DB::statement("ALTER TABLE waiver_acceptances ADD CONSTRAINT email_format CHECK (signer_email IS NULL OR signer_email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+[.][A-Za-z]{2,}$')");

        Schema::create('makeup_credits', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('rider_id', 36)->index();
            $table->string('source_booking_id', 36)->nullable();
            $table->integer('duration_minutes');
            $table->dateTime('issued_at')->useCurrent();
            $table->date('expiration_date')->nullable();
            $table->string('status', 50)->nullable()->default('available')->index();
            $table->string('redeemed_booking_id', 36)->nullable();
            $table->dateTime('redeemed_at')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->foreign('rider_id')->references('id')->on('riders');
            $table->foreign('source_booking_id')->references('id')->on('bookings');
            $table->foreign('redeemed_booking_id')->references('id')->on('bookings');
        });

        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('customer_id', 36);
            $table->string('provider', 50);
            $table->string('external_calendar_id', 255);
            $table->boolean('active')->default(false);
            $table->boolean('sync_enabled')->default(false);
            $table->string('sync_direction', 50)->nullable()->default('two_way');
            $table->dateTime('last_sync_at')->nullable();
            $table->datetimes();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_connections');
        Schema::dropIfExists('makeup_credits');
        Schema::dropIfExists('waiver_acceptances');
        Schema::dropIfExists('waivers');
    }
};
