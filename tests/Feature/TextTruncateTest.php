<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Support\Text;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 文字数基準の切り詰め（G4 ⑦ 50文字・G9 ① 2000文字・G17 ① 100文字）
 */
class TextTruncateTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_width_half_width_and_emoji_each_count_as_one_character(): void
    {
        $this->assertSame('あいう…', Text::truncate('あいうえお', 3));
        $this->assertSame('abc…', Text::truncate('abcde', 3));
        $this->assertSame('😀😀😀…', Text::truncate('😀😀😀😀', 3));
        $this->assertSame('あaい😀…', Text::truncate('あaい😀う', 4));
        $this->assertSame('ｱｲｳ…', Text::truncate('ｱｲｳｴｵ', 3)); // 半角カナも1文字
    }

    public function test_text_within_limit_is_unchanged_and_null_is_empty(): void
    {
        $this->assertSame('あいう', Text::truncate('あいう', 3));
        $this->assertSame('😀😀', Text::truncate('😀😀', 3));
        $this->assertSame('', Text::truncate(null, 3));
        $this->assertSame('', Text::truncate('', 3));
    }

    public function test_str_limit_chars_macro_and_difference_from_str_limit(): void
    {
        $fullWidth = str_repeat('あ', 60);

        $this->assertSame(str_repeat('あ', 50).'…', Str::limitChars($fullWidth, 50));
        // Str::limit は表示幅（全角＝2）で数えるため、全角は約半分になる
        $this->assertSame(str_repeat('あ', 25).'...', Str::limit($fullWidth, 50));
    }

    /**
     * ZWJ でつないだ絵文字は複数のコードポイントとして数える（mb_substr ベースのため）
     */
    public function test_zwj_emoji_counts_as_multiple_code_points(): void
    {
        $family = '👨‍👩‍👧'; // 5 コードポイント

        $this->assertSame(5, mb_strlen($family));
        $this->assertSame($family, Text::truncate($family, 5));
    }

    public function test_g4_excerpt_is_50_characters(): void
    {
        $this->withoutVite();
        Question::factory()->create(['body' => str_repeat('あ', 49).'い'.str_repeat('う', 10)]);

        $this->actingAs(User::factory()->create())->get('/home')
            ->assertSee(str_repeat('あ', 49).'い…')
            ->assertDontSee(str_repeat('あ', 49).'いう');
    }

    public function test_g9_target_answer_shows_full_text_up_to_2000_characters(): void
    {
        $this->withoutVite();
        $author = User::factory()->create();
        $question = Question::factory()->for($author)->create();
        $long = str_repeat('回', 1999).'了';
        Answer::factory()->for($question)->create(['body' => $long]);

        $html = $this->actingAs($author)->get(route('questions.show', $question))->getContent();
        $sheet = substr($html, strpos($html, 'id="best-sheet-'));

        $this->assertStringContainsString($long, $sheet);
        $this->assertStringNotContainsString($long.'…', $sheet);
    }

    public function test_g17_target_is_100_characters(): void
    {
        $this->withoutVite();
        $question = Question::factory()->create(['body' => str_repeat('通', 99).'報'.str_repeat('外', 5)]);

        $html = $this->actingAs(User::factory()->create())->get(route('questions.show', $question))->getContent();
        $sheet = substr($html, strpos($html, 'id="report-sheet-question"'));

        $this->assertStringContainsString(str_repeat('通', 99).'報…', $sheet);
        $this->assertStringNotContainsString('報外', $sheet);
    }
}
