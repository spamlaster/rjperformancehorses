<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waiver_acceptances', function (Blueprint $table) {
            $table->string('waiver_title')->nullable();
            $table->integer('waiver_version')->nullable();
            $table->longText('waiver_content')->nullable();
            $table->string('document_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('waiver_acceptances', function (Blueprint $table) {
            $table->dropColumn(['waiver_title', 'waiver_version', 'waiver_content', 'document_hash', 'ip_address', 'user_agent']);
        });
    }
};
