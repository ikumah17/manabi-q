<?php

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * is_best・rating・status は一括代入させない（ベストアンサー決定・管理操作でのみ更新）
 */
#[Fillable(['question_id', 'user_id', 'stamp_id', 'body'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_best' => 0,
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'is_best' => 'boolean',
            'rating' => 'integer',
            'status' => PostStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * 回答者
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 添付スタンプ（装飾のみ）
     *
     * @return BelongsTo<Stamp, $this>
     */
    public function stamp(): BelongsTo
    {
        return $this->belongsTo(Stamp::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * 公開中の回答のみ
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
     * ランキング・スタンプ獲得で数える回答：回答と質問の両方が公開中のもの
     */
    #[Scope]
    protected function countable(Builder $query): void
    {
        $query->where('answers.status', PostStatus::Published->value)
            ->whereHas('question', fn (Builder $question) => $question->where('status', PostStatus::Published->value));
    }
}
