<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('subject', 255);
            $table->datetimes();
            $table->unique(['provider', 'subject']);
        });
        Schema::create('rider_sign_in_codes', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('code_hash', 64);
            $table->dateTime('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('created_at');
        });
        Schema::table('riders', function (Blueprint $table) {
            $table->string('identity_key', 64)->nullable()->unique();
            $table->string('merged_into_id', 36)->nullable()->index();
        });

        // Preserve every historical row and its booking links. Only exact identities
        // within the same customer are grouped; different birthdays stay separate.
        $normalize = fn ($value) => mb_strtolower(preg_replace('/\\s+/u', ' ', trim((string) $value)));
        $canonical = [];
        foreach (DB::table('riders')->whereNull('deleted_at')->orderBy('created_at')->orderBy('id')->get() as $rider) {
            $owner = $rider->customer_id ? "customer:{$rider->customer_id}" : ($rider->email ? 'email:'.$normalize($rider->email) : null);
            if ($owner === null) {
                continue;
            }
            $key = hash('sha256', json_encode([$owner, $normalize($rider->first_name), $normalize($rider->last_name), $rider->date_of_birth ?? '']));
            if (isset($canonical[$key])) {
                DB::table('riders')->where('id', $rider->id)->update(['merged_into_id' => $canonical[$key]]);
            } else {
                $canonical[$key] = $rider->id;
                DB::table('riders')->where('id', $rider->id)->update(['identity_key' => $key]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('riders', fn (Blueprint $table) => $table->dropColumn(['identity_key', 'merged_into_id']));
        Schema::dropIfExists('rider_sign_in_codes');
        Schema::dropIfExists('social_identities');
    }
};
