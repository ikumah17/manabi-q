<?php

namespace Tests\Feature\View;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_mobile_layout_with_tabbar_back_button_and_fab(): void
    {
        $view = $this->blade(
            '<x-layouts.mobile header="質問詳細" title="質問詳細" back="/home" tab="home">本文<x-slot:fab><x-fab href="/answers/create" icon="✎">回答する</x-fab></x-slot:fab></x-layouts.mobile>'
        );

        $view->assertSee('<html lang="ja">', false)
            ->assertSee('<title>質問詳細 | まなびQ</title>', false)
            ->assertSee('mq-app--with-tabbar', false)
            ->assertSee('aria-label="戻る"', false)
            ->assertSee('href="/home"', false)
            ->assertSeeInOrder(['ホーム', '質問する', 'ランキング', 'マイページ'])
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="/answers/create"', false)
            ->assertSee('回答する');
    }

    public function test_mobile_layout_without_tab_shows_brand_and_no_tabbar(): void
    {
        $view = $this->blade('<x-layouts.mobile title="ログイン">フォーム</x-layouts.mobile>');

        $view->assertSee('<title>ログイン | まなびQ</title>', false)
            ->assertSee('<div class="mq-header__title">まなびQ</div>', false)
            ->assertDontSee('mq-tabbar', false)
            ->assertDontSee('aria-label="戻る"', false);
    }

    public function test_tabbar_marks_only_the_active_item(): void
    {
        $view = $this->blade('<x-tab-bar active="ranking" />');

        $this->assertSame(1, substr_count((string) $view, 'aria-current="page"'));
        $view->assertSeeInOrder(['is-active', 'ランキング'], false);
    }

    public function test_admin_layout_shows_menu_title_and_user_name(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => '管理者']));

        $view = $this->blade('<x-layouts.admin title="投稿管理" active="posts">一覧</x-layouts.admin>');

        $view->assertSee('まなびQ 管理')
            ->assertSeeInOrder(['ダッシュボード', '投稿管理', 'ユーザー管理', 'カテゴリ管理', '通報管理'])
            ->assertSee('<h1 class="mq-admin-title">投稿管理</h1>', false)
            ->assertSee('管理者 さん')
            ->assertSee('ログアウト');
    }

    public function test_form_errors_lists_messages_only_when_present(): void
    {
        // 実際のリクエストでは ShareErrorsFromSession ミドルウェアが $errors を全ビューに共有する
        view()->share('errors', new ViewErrorBag);
        $this->blade('<x-form-errors />')
            ->assertDontSee('mq-error', false);

        view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['title' => ['タイトルを入力してください']])));
        $this->blade('<x-form-errors />')
            ->assertSee('role="alert"', false)
            ->assertSee('タイトルを入力してください');
    }

    public function test_form_errors_can_read_a_named_bag(): void
    {
        view()->share('errors', (new ViewErrorBag)->put('report', new MessageBag(['reason' => ['通報理由を選択してください']])));

        $this->blade('<x-form-errors />')->assertDontSee('通報理由を選択してください');
        $this->blade('<x-form-errors bag="report" />')->assertSee('通報理由を選択してください');
    }

    public function test_flash_shows_success_message_from_session(): void
    {
        session()->flash('success', '通報を受け付けました');

        $this->blade('<x-flash />')
            ->assertSee('mq-flash--success', false)
            ->assertSee('通報を受け付けました');
    }

    public function test_badge_and_bottom_sheet_render(): void
    {
        $this->blade('<x-badge variant="ai">AI回答あり</x-badge>')
            ->assertSee('<span class="mq-badge mq-badge--ai">AI回答あり</span>', false);

        $this->blade('<x-bottom-sheet id="report-sheet" title="この投稿を通報する" :open="true">中身</x-bottom-sheet>')
            ->assertSee('id="report-sheet"', false)
            ->assertSee('offcanvas offcanvas-bottom mq-sheet', false)
            ->assertSee('data-mq-autoshow', false)
            ->assertSee('この投稿を通報する');
    }

    public function test_paginator_uses_design_style_for_mobile_and_admin(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 10), 30, 10, 2, ['path' => '/home']);

        $mobile = (string) $paginator->links();
        $this->assertStringContainsString('class="mq-pagination"', $mobile);
        $this->assertStringContainsString('<span class="mq-page is-active" aria-current="page">2</span>', $mobile);
        $this->assertStringContainsString('rel="prev"', $mobile);
        $this->assertStringContainsString('rel="next"', $mobile);

        $admin = (string) $paginator->links('pagination.admin');
        $this->assertStringContainsString('mq-pagination--admin', $admin);
    }

    public function test_dev_preview_routes_are_not_available_outside_local(): void
    {
        $this->get('/_dev/mobile')->assertNotFound();
        $this->get('/_dev/admin')->assertNotFound();
    }
}
