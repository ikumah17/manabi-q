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
        // データベース設計書「通報（reports）」
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->tinyInteger('reason')->comment('1：不適切 2：誹謗中傷 3：スパム 4：個人情報 5：その他');
            $table->string('detail', 500)->nullable();
            $table->tinyInteger('status')->default(0)->index()->comment('0：未対応、1：対応済み');
            $table->timestamps();

            // 同一投稿への重複通報防止（NULL を含むためアプリ側でもチェックする）
            $table->unique(['user_id', 'question_id', 'answer_id'], 'reports_user_question_answer_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
