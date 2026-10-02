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
        // データベース設計書「ユーザー（users）」
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->tinyInteger('role')->default(0)->comment('0：一般ユーザー、1：管理者');
            $table->tinyInteger('status')->default(1)->comment('0：利用停止、1：利用中');
            $table->rememberToken();
            $table->timestamps();
        });

        // セッション管理用（Laravel 標準。設計書の7テーブル外）
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
