<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    public function test_locale_and_timezone_are_japanese(): void
    {
        $this->assertSame('ja', app()->getLocale());
        $this->assertSame('Asia/Tokyo', config('app.timezone'));
        $this->assertSame('Asia/Tokyo', now()->getTimezone()->getName());
    }

    public function test_validation_messages_are_japanese(): void
    {
        $validator = Validator::make(
            ['title' => '', 'email' => 'not-an-email'],
            ['title' => 'required', 'email' => 'email'],
        );

        $this->assertSame('タイトルを入力してください', $validator->errors()->first('title'));
        $this->assertSame('メールアドレスを正しい形式で入力してください', $validator->errors()->first('email'));
    }

    public function test_gemini_settings_are_read_from_services_config(): void
    {
        $this->assertArrayHasKey('key', config('services.gemini'));
        $this->assertNotEmpty(config('services.gemini.model'));
    }

    /**
     * テストでは .env の本物の API キーを読み込まない（phpunit.xml で空にしている）
     */
    public function test_real_gemini_key_is_not_loaded_in_tests(): void
    {
        $this->assertEmpty(config('services.gemini.key'));
        $this->assertSame('https://gemini.test/v1beta', config('services.gemini.base_url'));
    }
}
