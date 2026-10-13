<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_lessons', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('rider_id', 36)->index();
            $table->string('lesson_type_id', 36);
            $table->string('horse_id', 36)->nullable()->index();
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->integer('duration_minutes');
            $table->string('status', 50)->nullable()->default('active');
            $table->text('notes')->nullable();
            $table->datetimes();
            $table->dateTime('deleted_at')->nullable();

            $table->foreign('rider_id')->references('id')->on('riders');
            $table->foreign('lesson_type_id')->references('id')->on('lesson_types');
            $table->foreign('horse_id')->references('id')->on('horses');
        });

        // start_time/end_time are business-local wall times (DATETIME, no zone), as in Postgres.
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('customer_id', 36)->index();
            $table->string('lesson_type_id', 36);
            // Display only; booking_horses is authoritative for horse conflicts.
            $table->string('horse_id', 36)->index();
            $table->dateTime('start_time')->index();
            $table->dateTime('end_time');
            $table->string('status', 50)->default('pending')->index();
            $table->string('booking_type', 50)->default('regular');
            $table->string('recurring_lesson_id', 36)->nullable();
            $table->integer('version')->nullable()->default(1);
            $table->text('notes')->nullable();
            $table->datetimes();
            $table->dateTime('deleted_at')->nullable()->index();

            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('lesson_type_id')->references('id')->on('lesson_types');
            $table->foreign('horse_id')->references('id')->on('horses');
            $table->foreign('recurring_lesson_id')->references('id')->on('recurring_lessons');
        });
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_status_check CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show'))");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_booking_type_check CHECK (booking_type IN ('regular', 'trial', 'makeup', 'recurring'))");

        Schema::create('booking_riders', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36)->index();
            $table->string('rider_id', 36)->index();
            $table->dateTime('created_at')->nullable();

            $table->unique(['booking_id', 'rider_id']);
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('rider_id')->references('id')->on('riders');
        });

        // One booking can reserve several horses (two-rider lessons).
        Schema::create('booking_horses', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36)->index();
            $table->string('horse_id', 36)->index();
            $table->dateTime('created_at')->nullable();

            $table->unique(['booking_id', 'horse_id']);
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('horse_id')->references('id')->on('horses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_horses');
        Schema::dropIfExists('booking_riders');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('recurring_lessons');
    }
};
