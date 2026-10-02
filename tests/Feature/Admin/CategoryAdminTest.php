<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use App\Support\CategoryName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * G16 カテゴリ管理（追加・行内編集・削除・重複判定・表示順）
 */
class CategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function store(array $data)
    {
        return $this->actingAs($this->admin)->from(route('admin.categories.index'))
            ->post(route('admin.categories.store'), $data);
    }

    private function update(Category $category, array $data)
    {
        return $this->actingAs($this->admin)->from(route('admin.categories.index'))
            ->put(route('admin.categories.update', $category), array_merge(['editing' => $category->id], $data));
    }

    public function test_list_is_in_display_order_with_question_counts_including_hidden(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 1));
        $b = Category::factory()->create(['name' => '課題・レポート', 'sort_order' => 2]);
        $a = Category::factory()->create(['name' => '授業', 'sort_order' => 1]);
        $this->travelBack();
        Question::factory()->for($a)->count(2)->create();
        Question::factory()->for($a)->hidden()->create();

        $this->actingAs($this->admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSeeTextInOrder(['カテゴリ名', '表示順', '追加する', '表示順', 'カテゴリ名', '質問数', '登録日', '操作',
                '1', '授業', '3', '2026/09/01', '編集', '削除', '2', '課題・レポート', '0'])
            ->assertSee('value="3"', false) // 表示順の初期値＝最大値+1
            ->assertSee('placeholder="新しいカテゴリ名（20文字以内）"', false);
    }

    public function test_store_with_message_and_default_sort_order(): void
    {
        Category::factory()->create(['sort_order' => 5]);

        $this->store(['name' => '就職・進路', 'sort_order' => ''])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'カテゴリを追加しました');

        $this->assertSame(6, Category::where('name', '就職・進路')->value('sort_order'));
    }

    public function test_default_sort_order_is_capped_at_999_and_same_order_is_sorted_by_id(): void
    {
        $first = Category::factory()->create(['name' => '先', 'sort_order' => 999]);

        $this->actingAs($this->admin)->get(route('admin.categories.index'))->assertSee('value="999"', false);
        $this->store(['name' => '後'])->assertSessionHas('success');

        $this->assertSame(999, Category::where('name', '後')->value('sort_order'));
        $this->assertSame(['先', '後'], Category::ordered()->pluck('name')->all());
        $this->assertTrue($first->id < Category::where('name', '後')->value('id'));
    }

    public function test_validation_of_name_and_sort_order(): void
    {
        $this->store(['name' => ''])->assertSessionHasErrors(['name' => 'カテゴリ名を入力してください']);
        $this->store(['name' => str_repeat('あ', 21)])->assertSessionHasErrors(['name' => 'カテゴリ名は20文字以内で入力してください']);
        foreach ([-1, 1000, 'abc', '1.5'] as $order) {
            $this->store(['name' => '新規', 'sort_order' => $order])
                ->assertSessionHasErrors(['sort_order' => '表示順は0〜999の範囲で入力してください']);
        }
        $this->store(['name' => str_repeat('あ', 20), 'sort_order' => 999])->assertSessionHasNoErrors();
        $this->store(['name' => '零', 'sort_order' => 0])->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function duplicateNames(): array
    {
        return [
            'same' => ['授業', '授業'],
            'surrounding spaces (full-width)' => ['授業', '　授業　'],
            'full-width alphanumerics' => ['Java', 'Ｊａｖａ'],
            'letter case' => ['Java', 'JAVA'],
            'half-width katakana' => ['カテゴリ', 'ｶﾃｺﾞﾘ'],
            'NFKC: roman numeral' => ['IV', 'Ⅳ'],
            'NFKC: circled digit' => ['1年生', '①年生'],
            'NFKC: parenthesized ideograph' => ['(株)', '㈱'],
        ];
    }

    #[DataProvider('duplicateNames')]
    public function test_duplicate_names_are_rejected(string $existing, string $input): void
    {
        Category::factory()->create(['name' => $existing]);

        $this->store(['name' => $input])->assertSessionHasErrors(['name' => '同じ名前のカテゴリが既に存在します']);
        $this->assertSame(1, Category::count());
    }

    public function test_inner_spaces_are_not_collapsed(): void
    {
        Category::factory()->create(['name' => '授業']);

        $this->store(['name' => '授 業'])->assertSessionHasNoErrors();
        $this->assertSame(2, Category::count());
    }

    public function test_name_is_saved_trimmed_but_otherwise_as_entered(): void
    {
        $this->store(['name' => '　Ｊａｖａ入門　'])->assertSessionHasNoErrors();

        $this->assertSame('Ｊａｖａ入門', Category::sole()->name);
        $this->assertSame('java入門', CategoryName::key('Ｊａｖａ入門'));
    }

    /**
     * DB の UNIQUE 制約で拒否された場合（MySQL の utf8mb4_unicode_ci で「は」と「ば」を同じとみなす場合や同時登録）
     */
    public function test_database_unique_violation_is_shown_as_duplicate_message(): void
    {
        Category::creating(function (Category $category) {
            DB::table('categories')->insert(['name' => $category->name, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        });

        $this->store(['name' => 'ばし'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasErrors(['name' => CategoryRequest::DUPLICATE_MESSAGE]);
    }

    public function test_update_with_message_and_self_is_excluded_from_duplicate_check(): void
    {
        $category = Category::factory()->create(['name' => 'java', 'sort_order' => 3]);

        $this->update($category, ['name' => 'Java', 'sort_order' => 7])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'カテゴリを更新しました');

        $category->refresh();
        $this->assertSame(['Java', 7], [$category->name, $category->sort_order]);
    }

    public function test_update_validation_errors_go_to_edit_bag(): void
    {
        Category::factory()->create(['name' => '授業', 'sort_order' => 1]);
        $target = Category::factory()->create(['name' => '学校生活', 'sort_order' => 2]);

        $this->update($target, ['name' => '学校生活', 'sort_order' => 1000])
            ->assertSessionHasErrorsIn('edit', ['sort_order' => '表示順は0〜999の範囲で入力してください']);
        $this->update($target, ['name' => '授業', 'sort_order' => 2])
            ->assertSessionHasErrorsIn('edit', ['name' => '同じ名前のカテゴリが既に存在します']);

        $this->assertSame('学校生活', $target->refresh()->name);
    }

    /**
     * 注意：テストでは、直前のレスポンスに assertSessionHasErrorsIn を続けると次のリクエストで old 入力が読めなくなるため、
     * ここでは送信後に画面の表示で確認する
     */
    public function test_update_error_reopens_the_row_in_edit_mode_with_input(): void
    {
        Category::factory()->create(['name' => '授業', 'sort_order' => 1]);
        $target = Category::factory()->create(['name' => '学校生活', 'sort_order' => 2]);

        $this->update($target, ['name' => '授業', 'sort_order' => 5]);
        $html = $this->actingAs($this->admin)->get(route('admin.categories.index'))->getContent();

        $this->assertSame(1, substr_count($html, 'mq-edit-row is-editing'));
        $this->assertStringContainsString('同じ名前のカテゴリが既に存在します', $html);
        $this->assertMatchesRegularExpression('/form="edit-category-'.$target->id.'" name="sort_order"[^>]*value="5"/', $html);
        $this->assertSame('学校生活', $target->refresh()->name);
    }

    public function test_delete_empty_category_with_message(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'カテゴリを削除しました');

        $this->assertModelMissing($category);
    }

    public function test_category_with_questions_including_hidden_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Question::factory()->for($category)->hidden()->create();

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error', '質問が登録されているカテゴリは削除できません');

        $this->assertModelExists($category);
    }

    /**
     * 件数の確認のあとに質問が追加された場合：外部キー（RESTRICT）で拒否され、画面にメッセージを出す
     */
    public function test_question_added_during_delete_is_reported_without_server_error(): void
    {
        $category = Category::factory()->create();
        Category::deleting(function (Category $deleting) {
            Question::factory()->for($deleting)->create();
        });

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', CategoryController::RACE_MESSAGE);

        $this->assertModelExists($category);
    }

    public function test_delete_uses_bootstrap_modal_with_delete_method_and_escaped_name(): void
    {
        $category = Category::factory()->create(['name' => '<b>"x"</b>']);

        $html = $this->actingAs($this->admin)->get(route('admin.categories.index'))->getContent();

        $this->assertStringContainsString('data-confirm-action="'.route('admin.categories.destroy', $category).'"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringNotContainsString('<b>"x"</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;&quot;x&quot;&lt;/b&gt;', $html);
    }

    public function test_general_user_gets_403(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.categories.store'), ['name' => 'x'])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.categories.destroy', $category))->assertForbidden();
        $this->assertModelExists($category);
    }
}
