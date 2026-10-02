<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 初期データ（カテゴリ・スタンプのみ）。
     * 管理者アカウントは `php artisan admin:create` で作成する。
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            StampSeeder::class,
        ]);
    }
}
