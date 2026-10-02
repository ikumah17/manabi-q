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
}
