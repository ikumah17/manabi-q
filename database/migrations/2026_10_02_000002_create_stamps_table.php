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
        // データベース設計書「スタンプ（stamps）」
        Schema::create('stamps', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('image_path', 255);
            $table->integer('required_count')->comment('10／50／100');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamps');
    }
};
