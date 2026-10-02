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
        // データベース設計書「回答（answers）」
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('stamp_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('body');
            $table->tinyInteger('is_best')->default(0)->comment('0：通常、1：ベストアンサー');
            $table->tinyInteger('rating')->nullable()->comment('1〜5（ベストアンサーのみ）');
            $table->tinyInteger('status')->default(1)->comment('0：非表示、1：公開');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
