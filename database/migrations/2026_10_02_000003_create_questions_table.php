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
        // データベース設計書「質問（questions）」
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->index()->constrained()->restrictOnDelete();
            $table->string('title', 100);
            $table->text('body');
            $table->tinyInteger('use_ai')->default(0)->comment('0：不要、1：希望');
            $table->tinyInteger('is_resolved')->default(0)->comment('0：未解決、1：解決済み');
            $table->tinyInteger('status')->default(1)->comment('0：非表示、1：公開');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
