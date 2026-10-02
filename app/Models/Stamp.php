<?php

namespace App\Models;

use Database\Factories\StampFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'image_path', 'required_count'])]
class Stamp extends Model
{
    /** @use HasFactory<StampFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'required_count' => 'integer',
        ];
    }

    /**
     * @return HasMany<Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * 必要回答数の少ない順に並べる
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('required_count')->orderBy('id');
    }

    /**
     * 画像の URL（image_path は public からの相対パス）
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => asset($this->image_path));
    }
}
