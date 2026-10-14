<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_lessons', function (Blueprint $table) {
            $table->string('customer_id', 36)->nullable()->index();
            $table->date('starts_on')->nullable();
            $table->string('management_token_hash', 64)->nullable()->unique();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers');
        });

        foreach (['horses' => 'horse', 'riders' => 'rider'] as $target => $singular) {
            Schema::create("recurring_lesson_{$target}", function (Blueprint $table) use ($target, $singular) {
                $table->string('recurring_lesson_id', 36);
                $table->string("{$singular}_id", 36);
                $table->primary(['recurring_lesson_id', "{$singular}_id"]);
                $table->foreign('recurring_lesson_id')->references('id')->on('recurring_lessons')->cascadeOnDelete();
                $table->foreign("{$singular}_id")->references('id')->on($target);
            });
        }

        Schema::create('recurring_lesson_skips', function (Blueprint $table) {
            $table->string('recurring_lesson_id', 36);
            $table->date('date');
            $table->dateTime('created_at');
            $table->primary(['recurring_lesson_id', 'date']);
            $table->foreign('recurring_lesson_id')->references('id')->on('recurring_lessons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_lesson_skips');
        Schema::dropIfExists('recurring_lesson_riders');
        Schema::dropIfExists('recurring_lesson_horses');
        Schema::table('recurring_lessons', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'starts_on', 'management_token_hash', 'cancelled_at']);
        });
    }
};
