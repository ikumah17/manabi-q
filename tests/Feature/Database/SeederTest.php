<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\Like;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_categories_and_stamps_only(): void
    {
        $this->seed();

        $this->assertSame(
            ['授業', '課題・レポート', '学校生活', '就職・進路', 'その他'],
            Category::ordered()->pluck('name')->all(),
        );
        $this->assertSame([10, 50, 100], Stamp::ordered()->pluck('required_count')->all());
        $this->assertSame(0, User::count(), '管理者はシーダーで作らない');
        $this->assertSame(0, Like::count(), 'いいねはシーダーで作らない（likes テーブルはマスタではない）');

        foreach (Stamp::all() as $stamp) {
            $this->assertFileExists(public_path($stamp->image_path));
        }
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(5, Category::count());
        $this->assertSame(3, Stamp::count());
    }
}
