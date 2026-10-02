<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * G16 カテゴリ管理
 */
class CategoryController extends Controller
{
    public const MAX_SORT_ORDER = 999;

    public const HAS_QUESTIONS_MESSAGE = '質問が登録されているカテゴリは削除できません';

    public const RACE_MESSAGE = '質問が追加されたため削除できませんでした。もう一度確認してください';

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::ordered()->withCount('questions')->get(),
            'nextSortOrder' => $this->nextSortOrder(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category([
            'name' => $request->validated('name'),
            'sort_order' => $request->validated('sort_order') ?? $this->nextSortOrder(),
        ]);

        if (! $this->saveOrDuplicate($category)) {
            return back()->withErrors(['name' => CategoryRequest::DUPLICATE_MESSAGE])->withInput();
        }

        return redirect()->route('admin.categories.index')->with('success', 'カテゴリを追加しました');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->name = $request->validated('name');
        $category->sort_order = $request->validated('sort_order') ?? $this->nextSortOrder();

        if (! $this->saveOrDuplicate($category)) {
            return back()->withErrors(['name' => CategoryRequest::DUPLICATE_MESSAGE], 'edit')->withInput();
        }

        return redirect()->route('admin.categories.index')->with('success', 'カテゴリを更新しました');
    }

    /**
     * ⑦ 削除（質問が紐づく場合は削除不可。非表示の質問も含む）
     */
    public function destroy(Category $category): RedirectResponse
    {
        try {
            $deleted = DB::transaction(function () use ($category) {
                $locked = Category::whereKey($category->id)->lockForUpdate()->first();

                if ($locked === null) {
                    return true; // 既に削除されている
                }

                if ($locked->questions()->exists()) {
                    return false;
                }

                $locked->delete();

                return true;
            });
        } catch (QueryException $e) {
            // 確認のあとに質問が追加され、外部キー（RESTRICT）で削除が拒否された場合
            if ($this->isForeignKeyViolation($e)) {
                return redirect()->route('admin.categories.index')->with('error', self::RACE_MESSAGE);
            }

            throw $e;
        }

        return $deleted
            ? redirect()->route('admin.categories.index')->with('success', 'カテゴリを削除しました')
            : redirect()->route('admin.categories.index')->with('error', self::HAS_QUESTIONS_MESSAGE);
    }

    /**
     * ② 表示順の初期値：最大値+1（999 を上限。同じ表示順は ID 順で並べる）
     */
    private function nextSortOrder(): int
    {
        return min(((int) Category::max('sort_order')) + 1, self::MAX_SORT_ORDER);
    }

    /**
     * DB の UNIQUE 制約で拒否された場合は false（照合順序 utf8mb4_unicode_ci は「は」と「ば」なども同じとみなすため、
     * アプリの判定を通っても拒否されることがある。同時の登録も含む）
     */
    private function saveOrDuplicate(Category $category): bool
    {
        try {
            $category->save();
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    private function isForeignKeyViolation(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1451                     // MySQL：Cannot delete or update a parent row
            || str_contains($e->getMessage(), 'FOREIGN KEY constraint failed'); // SQLite
    }
}
