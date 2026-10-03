<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * is_resolved・status は一括代入させない（ベストアンサー決定・管理操作でのみ更新）
 */
#[Fillable(['user_id', 'category_id', 'title', 'body', 'use_ai'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'use_ai' => 0,
        'is_resolved' => 0,
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'use_ai' => 'boolean',
            'is_resolved' => 'boolean',
            'status' => PostStatus::class,
        ];
    }

    /**
     * 投稿者
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasOne<AiAnswer, $this>
     */
    public function aiAnswer(): HasOne
    {
        return $this->hasOne(AiAnswer::class);
    }

    /**
     * @return HasMany<Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * ベストアンサー（質問1件につき最大1件）
     *
     * @return HasOne<Answer, $this>
     */
    public function bestAnswer(): HasOne
    {
        return $this->hasOne(Answer::class)->where('is_best', 1);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * 公開中の質問のみ
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PostStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published;
    }

    /**
     * 一覧表示用：投稿者・カテゴリ・公開中の回答数（answers_count）・AI回答の有無（ai_answer_exists）
     */
    #[Scope]
    protected function forList(Builder $query): void
    {
        $query->with(['user', 'category'])
            ->withCount(['answers' => fn (Builder $answers) => $answers->where('status', PostStatus::Published->value)])
            ->withExists('aiAnswer');
    }

    /**
     * キーワード検索（G4 ②）：空白で区切った各語がタイトルか本文に含まれる（AND）。
     * LIKE の特殊文字（% _）は文字として扱う
     */
    #[Scope]
    protected function search(Builder $query, string $keyword): void
    {
        $words = preg_split('/[\s　]+/u', trim($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word).'%';

            $query->where(function (Builder $q) use ($pattern) {
                $q->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("body LIKE ? ESCAPE '!'", [$pattern]);
            });
        }
    }

    /**
     * 並び替え（G4 ⑤）：new 新着順 / unresolved 未解決のみ（新着順）/ answers 回答の多い順
     * answers は forList() の answers_count を使う
     */
    #[Scope]
    protected function sorted(Builder $query, string $sort): void
    {
        if ($sort === 'unresolved') {
            $query->where('is_resolved', 0);
        }

        if ($sort === 'answers') {
            $query->orderByDesc('answers_count');
        }

        $query->latest()->orderByDesc('id');
    }
}
