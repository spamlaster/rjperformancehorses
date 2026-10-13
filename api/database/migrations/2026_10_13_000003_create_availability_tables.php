<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rylie's normal weekly windows. day_of_week: 0 = Sunday … 6 = Saturday.
        Schema::create('instructor_availability', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('active')->default(true);
            $table->datetimes();

            $table->unique(['day_of_week', 'start_time']);
            $table->index(['day_of_week', 'active']);
        });
        DB::statement('ALTER TABLE instructor_availability ADD CONSTRAINT instructor_availability_dow CHECK (day_of_week BETWEEN 0 AND 6)');

        Schema::create('horse_recurring_availability', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('horse_id', 36)->index();
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('active')->default(true);
            $table->datetimes();

            $table->unique(['horse_id', 'day_of_week', 'start_time'], 'horse_recurring_avail_unique');
            $table->index(['day_of_week', 'horse_id', 'active'], 'horse_recurring_avail_day_active');
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE horse_recurring_availability ADD CONSTRAINT horse_recurring_availability_dow CHECK (day_of_week BETWEEN 0 AND 6)');

        // Date-specific exceptions. horse_id NULL = instructor block.
        Schema::create('blocked_times', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('horse_id', 36)->nullable()->index();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason', 255)->nullable();
            $table->datetimes();

            $table->index(['date', 'start_time', 'end_time']);
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
        });

        // Single-row settings table. The row is also the booking lock (see BookingService).
        Schema::create('business_config', function (Blueprint $table) {
            $table->string('config_key', 50)->primary();
            $table->string('config_value', 100);
            $table->dateTime('updated_at')->nullable();
        });
        DB::statement("ALTER TABLE business_config ADD CONSTRAINT business_config_key CHECK (config_key = 'business_timezone')");
        DB::table('business_config')->insert([
            'config_key' => 'business_timezone',
            'config_value' => 'America/Denver',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_config');
        Schema::dropIfExists('blocked_times');
        Schema::dropIfExists('horse_recurring_availability');
        Schema::dropIfExists('instructor_availability');
    }
};
