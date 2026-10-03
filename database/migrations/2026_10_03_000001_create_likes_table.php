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
        // データベース設計書「いいね（likes）」：question_id と answer_id はどちらか一方だけ（アプリ側でチェック）
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_id')->nullable()->index()->constrained()->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->index()->constrained()->cascadeOnDelete();
            $table->timestamps();

            // 同じ対象へのいいねは1人1回（NULL どうしは重複とみなされないため、質問と回答のいいねは衝突しない）
            $table->unique(['user_id', 'question_id'], 'likes_user_question_unique');
            $table->unique(['user_id', 'answer_id'], 'likes_user_answer_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
