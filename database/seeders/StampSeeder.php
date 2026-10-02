<?php

namespace Database\Seeders;

use App\Models\Stamp;
use Illuminate\Database\Seeder;

/**
 * 装飾スタンプ（回答数 10 / 50 / 100 で獲得）
 */
class StampSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([10, 50, 100] as $count) {
            Stamp::updateOrCreate(
                ['required_count' => $count],
                ['name' => "{$count}回答達成", 'image_path' => "images/stamps/stamp-{$count}.svg"],
            );
        }
    }
}
