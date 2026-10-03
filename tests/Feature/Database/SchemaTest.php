<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * データベース設計書の8テーブル（いいね likes を含む）とカラムが揃っていること
     */
    public function test_design_tables_and_columns_exist(): void
    {
        $expected = [
            'users' => ['id', 'name', 'email', 'password', 'role', 'status', 'remember_token', 'created_at', 'updated_at'],
            'categories' => ['id', 'name', 'sort_order', 'created_at', 'updated_at'],
            'stamps' => ['id', 'name', 'image_path', 'required_count', 'created_at', 'updated_at'],
            'questions' => ['id', 'user_id', 'category_id', 'title', 'body', 'use_ai', 'is_resolved', 'status', 'created_at', 'updated_at'],
            'ai_answers' => ['id', 'question_id', 'body', 'model_name', 'created_at', 'updated_at'],
            'answers' => ['id', 'question_id', 'user_id', 'stamp_id', 'body', 'is_best', 'rating', 'status', 'created_at', 'updated_at'],
            'reports' => ['id', 'user_id', 'question_id', 'answer_id', 'reason', 'detail', 'status', 'created_at', 'updated_at'],
            'likes' => ['id', 'user_id', 'question_id', 'answer_id', 'created_at', 'updated_at'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "{$table} テーブルがありません");
            $this->assertEqualsCanonicalizing($columns, Schema::getColumnListing($table), "{$table} のカラムが設計と異なります");
        }
    }

    /**
     * 設計で削除を決めたものが無いこと（Q4）
     */
    public function test_removed_framework_items_do_not_exist(): void
    {
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
        $this->assertFalse(Schema::hasColumn('users', 'email_verified_at'));
        $this->assertTrue(Schema::hasTable('sessions'));
    }

    /**
     * 外部キーと削除時の動作（連動削除の範囲）を固定する。
     * 連動（CASCADE）は questions → ai_answers / answers / reports / likes、answers → reports / likes の一方向のみで循環しない。
     * ユーザー・カテゴリ・スタンプは参照されていると削除できない（RESTRICT）。best_answer_id 列は無い（answers.is_best で表す）
     */
    public function test_foreign_keys_and_delete_actions(): void
    {
        $actual = [];
        foreach (['questions', 'ai_answers', 'answers', 'reports', 'likes'] as $table) {
            foreach (Schema::getForeignKeys($table) as $fk) {
                $actual[] = $table.'.'.implode(',', $fk['columns']).' -> '.$fk['foreign_table'].' '.strtolower($fk['on_delete']);
            }
        }
        sort($actual);

        $this->assertSame([
            'ai_answers.question_id -> questions cascade',
            'answers.question_id -> questions cascade',
            'answers.stamp_id -> stamps restrict',
            'answers.user_id -> users restrict',
            'likes.answer_id -> answers cascade',
            'likes.question_id -> questions cascade',
            'likes.user_id -> users restrict',
            'questions.category_id -> categories restrict',
            'questions.user_id -> users restrict',
            'reports.answer_id -> answers cascade',
            'reports.question_id -> questions cascade',
            'reports.user_id -> users restrict',
        ], $actual);
        $this->assertFalse(Schema::hasColumn('questions', 'best_answer_id'));
        $this->assertFalse(Schema::hasColumn('answers', 'best_answer_id'));
    }

    /**
     * 設計書のインデックス（ユニーク制約を含む）
     */
    public function test_design_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('users', 'users_email_unique'));
        $this->assertTrue(Schema::hasIndex('categories', 'categories_name_unique'));
        $this->assertTrue(Schema::hasIndex('questions', 'questions_user_id_index'));
        $this->assertTrue(Schema::hasIndex('questions', 'questions_category_id_index'));
        $this->assertTrue(Schema::hasIndex('ai_answers', 'ai_answers_question_id_unique'));
        $this->assertTrue(Schema::hasIndex('answers', 'answers_question_id_index'));
        $this->assertTrue(Schema::hasIndex('answers', 'answers_user_id_index'));
        $this->assertTrue(Schema::hasIndex('reports', 'reports_status_index'));
        $this->assertTrue(Schema::hasIndex('reports', 'reports_user_question_answer_unique'));
        $this->assertTrue(Schema::hasIndex('likes', 'likes_user_question_unique'));
        $this->assertTrue(Schema::hasIndex('likes', 'likes_user_answer_unique'));
        $this->assertTrue(Schema::hasIndex('likes', 'likes_question_id_index'));
        $this->assertTrue(Schema::hasIndex('likes', 'likes_answer_id_index'));
    }
}
