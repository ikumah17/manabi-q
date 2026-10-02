<?php

namespace App\Services\Admin;

use App\Enums\PostStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * G13 管理者ダッシュボードの集計
 *
 * - 管理画面の集計は非表示の投稿も含める（未解決の質問は「公開中かつ未解決」）
 * - 月の境界はアプリのタイムゾーン（Asia/Tokyo）で PHP 側で計算し、DB の日付関数は使わない
 */
class DashboardStats
{
    /**
     * ② 集計サマリー
     *
     * @return array{users: int, questions: int, answers: int, unresolved: int, pendingReports: int}
     */
    public function summary(): array
    {
        return [
            'users' => User::where('role', UserRole::User->value)->count(), // 一般ユーザーのみ（利用停止を含む）
            'questions' => Question::count(),
            'answers' => Answer::count(),
            'unresolved' => Question::published()->where('is_resolved', 0)->count(),
            'pendingReports' => Report::where('status', ReportStatus::Pending->value)->count(),
        ];
    }

    /**
     * ③ 質問投稿数の推移（今月を含む直近 $months か月。0件の月も含める）
     *
     * @return Collection<int, array{label: string, start: string, count: int}>
     */
    public function monthlyQuestions(int $months = 7): Collection
    {
        $current = CarbonImmutable::now()->startOfMonth();

        return collect(range($months - 1, 0))->map(function (int $ago) use ($current) {
            $start = $current->subMonthsNoOverflow($ago);
            $end = $start->addMonthNoOverflow();

            return [
                'label' => $start->month.'月',
                'start' => $start->format('Y-m'),
                'count' => Question::where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            ];
        });
    }

    /**
     * ④ カテゴリ別質問数（表示順。0件のカテゴリも含める）
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function questionsByCategory(): Collection
    {
        return Category::ordered()->withCount('questions')->get()
            ->map(fn (Category $category) => ['label' => $category->name, 'count' => (int) $category->questions_count]);
    }

    /**
     * ⑤ 最近の投稿（質問・回答をまとめた新着 $limit 件。非表示の投稿も含める）
     *
     * @return Collection<int, array{type: string, content: string, user: string, created_at: CarbonInterface, hidden: bool}>
     */
    public function recentPosts(int $limit = 5): Collection
    {
        $questions = Question::with('user')->latest()->orderByDesc('id')->limit($limit)->get()
            ->map(fn (Question $question) => [
                'type' => 'question',
                'content' => $question->title,
                'user' => $question->user->name,
                'created_at' => $question->created_at,
                'hidden' => $question->status === PostStatus::Hidden,
            ]);

        $answers = Answer::with(['user', 'question'])->latest()->orderByDesc('id')->limit($limit)->get()
            ->map(fn (Answer $answer) => [
                'type' => 'answer',
                'content' => Str::limitChars(Str::squish($answer->body), 50),
                'user' => $answer->user->name,
                'created_at' => $answer->created_at,
                'hidden' => $answer->status === PostStatus::Hidden || $answer->question->status === PostStatus::Hidden,
            ]);

        return $questions->concat($answers)
            ->sortByDesc(fn (array $post) => $post['created_at']->getTimestamp())
            ->take($limit)
            ->values();
    }
}
