<?php

namespace Tests\Feature\Ai;

use App\Services\AiAnswer\ResponseFormatter;
use Tests\TestCase;

/**
 * Gemini の応答の整形（Markdown 記号の除去・2000文字の切り詰め）
 */
class ResponseFormatterTest extends TestCase
{
    public function test_simple_markdown_is_removed(): void
    {
        $input = "## 解き方\n**特性方程式**を立てます。\n\n* 手順1\n- 手順2\n```\nコード\n```\n__強調__";

        $this->assertSame("解き方\n特性方程式を立てます。\n\n・手順1\n・手順2\nコード\n強調", (new ResponseFormatter)->format($input));
    }

    public function test_blank_lines_are_collapsed_and_trimmed(): void
    {
        $this->assertSame("前\n\n後", (new ResponseFormatter)->format("\r\n\n前\r\n\r\n\r\n\r\n後  \n\n"));
    }

    public function test_text_is_truncated_to_2000_characters(): void
    {
        $formatted = (new ResponseFormatter)->format(str_repeat('あ', 2500));

        $this->assertSame(2000, mb_strlen($formatted));
    }

    public function test_html_is_kept_as_text_for_escaping_at_display(): void
    {
        $this->assertSame('<script>alert(1)</script>', (new ResponseFormatter)->format('<script>alert(1)</script>'));
    }
}
