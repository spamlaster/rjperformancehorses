<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Horses are scheduling resources; ownership is tracked in horse_owner_relationships.
        Schema::create('horses', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name', 100)->unique();
            $table->string('breed', 50)->nullable();
            $table->integer('age')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('height', 20)->nullable();
            $table->text('temperament')->nullable();
            $table->string('experience_level', 50)->nullable()->default('beginner_friendly');
            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->datetimes();
            $table->dateTime('deleted_at')->nullable()->index();
        });

        Schema::create('horse_owners', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->datetimes();
        });

        Schema::create('horse_owner_relationships', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('horse_id', 36);
            $table->string('owner_id', 36);
            $table->string('ownership_type', 50)->nullable()->default('owned');
            $table->text('notes')->nullable();
            $table->datetimes();

            $table->unique(['horse_id', 'owner_id']);
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
            $table->foreign('owner_id')->references('id')->on('horse_owners')->cascadeOnDelete();
        });

        // Lesson types use the active flag; there is no deleted_at.
        Schema::create('lesson_types', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('duration_minutes');
            $table->decimal('price', 8, 2);
            $table->string('difficulty_level', 50)->nullable()->default('beginner');
            $table->integer('max_group_size')->nullable()->default(1);
            $table->integer('required_horse_count')->default(1);
            $table->integer('required_rider_count')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->datetimes();
        });
        DB::statement('ALTER TABLE lesson_types ADD CONSTRAINT required_horse_count_positive CHECK (required_horse_count >= 1)');
        DB::statement('ALTER TABLE lesson_types ADD CONSTRAINT required_rider_count_positive CHECK (required_rider_count >= 1)');

        Schema::create('horse_lesson_types', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('horse_id', 36)->index();
            $table->string('lesson_type_id', 36)->index();
            $table->boolean('eligible')->default(true);
            $table->dateTime('created_at')->nullable();

            $table->unique(['horse_id', 'lesson_type_id']);
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
            $table->foreign('lesson_type_id')->references('id')->on('lesson_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horse_lesson_types');
        Schema::dropIfExists('lesson_types');
        Schema::dropIfExists('horse_owner_relationships');
        Schema::dropIfExists('horse_owners');
        Schema::dropIfExists('horses');
    }
};
