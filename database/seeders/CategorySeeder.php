<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 初期カテゴリ（G16 の画面設計どおり：Q37）
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = ['授業', '課題・レポート', '学校生活', '就職・進路', 'その他'];

        foreach ($names as $index => $name) {
            Category::updateOrCreate(['name' => $name], ['sort_order' => $index + 1]);
        }
    }
}
