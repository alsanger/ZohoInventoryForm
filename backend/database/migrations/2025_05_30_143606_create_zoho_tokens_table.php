<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zoho_tokens', function (Blueprint $table) {
            $table->id();
            // 'access_token' для хранения токена доступа Zoho
            $table->text('access_token');
            // 'refresh_token' для хранения токена обновления Zoho
            $table->text('refresh_token');
            // 'expires_at' для хранения времени истечения access_token
            $table->datetime('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoho_tokens');
    }
};
