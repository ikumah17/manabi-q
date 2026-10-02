<?php

namespace Tests\Feature\View;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * エラー画面（resources/views/errors）の日本語表示
 * - 確認用のルートはテスト内でだけ登録する（routes/web.php には置かない）
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    /** Laravel 標準のエラー画面の英語の文言 */
    private const ENGLISH_DEFAULTS = [
        'Forbidden', 'Not Found', 'Page Expired', 'Too Many Requests',
        'Method Not Allowed', 'Server Error', 'Service Unavailable',
    ];

    private function assertJapaneseErrorPage($response, int $status, string $heading, string $message): void
    {
        $response->assertStatus($status)
            ->assertSee('<html lang="ja">', false)
            ->assertSee('name="viewport"', false)
            ->assertSee($heading)
            ->assertSee($message)
            ->assertSee('トップへ戻る')
            ->assertSee('href="'.url('/').'"', false)
            ->assertDontSee('build/assets', false)
            ->assertDontSee('history.back', false);

        foreach (self::ENGLISH_DEFAULTS as $english) {
            $response->assertDontSee($english);
        }
    }

    public function test_403_page_is_japanese(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin');

        $this->assertJapaneseErrorPage($response, 403, 'アクセスできません', 'この操作を行う権限がありません。');
    }

    public function test_404_page_is_japanese_for_unknown_url_and_missing_question(): void
    {
        $this->assertJapaneseErrorPage(
            $this->get('/no-such-page'),
            404, 'ページが見つかりません', '削除されたか、非公開になっている可能性があります。'
        );

        $this->assertJapaneseErrorPage(
            $this->actingAs(User::factory()->create())->get('/questions/999999'),
            404, 'ページが見つかりません', '削除されたか、非公開になっている可能性があります。'
        );
    }

    public function test_419_page_is_japanese_when_csrf_token_mismatches(): void
    {
        Route::post('/_test/token-mismatch', fn () => throw new TokenMismatchException('CSRF token mismatch.'));

        $response = $this->post('/_test/token-mismatch');

        $this->assertJapaneseErrorPage(
            $response, 419, 'ページの有効期限が切れました',
            'しばらく操作がなかったため、ページの有効期限が切れました。トップへ戻って、もう一度操作してください。'
        );
        $response->assertDontSee('CSRF token mismatch.');
    }

    public function test_429_page_is_japanese(): void
    {
        Route::get('/_test/too-many', fn () => abort(429));

        $this->assertJapaneseErrorPage(
            $this->get('/_test/too-many'),
            429, 'アクセスが集中しています', 'しばらく時間をおいて、再度お試しください。'
        );
    }

    public function test_405_uses_generic_4xx_page(): void
    {
        // /logout は POST のみ
        $response = $this->get('/logout');

        $this->assertJapaneseErrorPage(
            $response, 405, 'リクエストを処理できませんでした', 'URL が正しいか確認し、トップから操作し直してください。'
        );
        $response->assertSee('エラー 405');
    }

    public function test_500_page_hides_exception_message_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/server-error', fn () => throw new RuntimeException('内部の詳細 SECRET_DETAIL_123'));

        $response = $this->get('/_test/server-error');

        $this->assertJapaneseErrorPage(
            $response, 500, 'システムエラーが発生しました', '時間をおいて、再度お試しください。'
        );
        $response->assertDontSee('SECRET_DETAIL_123')
            ->assertDontSee('内部の詳細')
            ->assertDontSee('RuntimeException');
    }

    public function test_503_page_is_japanese(): void
    {
        Route::get('/_test/unavailable', fn () => abort(503));

        $this->assertJapaneseErrorPage(
            $this->get('/_test/unavailable'),
            503, 'メンテナンス中です', 'しばらくお待ちください。'
        );
    }
}
