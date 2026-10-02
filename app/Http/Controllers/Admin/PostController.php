<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Support\Like;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * G14 投稿管理（質問・回答をまとめた一覧）
 */
class PostController extends Controller
{
    /**
     * ② 種別の選択肢
     */
    public const TYPES = ['all' => 'すべて', 'question' => '質問', 'answer' => '回答'];

    /**
     * ③ 状態の選択肢
     */
    public const STATUSES = ['all' => 'すべて', 'published' => '公開', 'hidden' => '非表示'];

    public function index(Request $request): View
    {
        $keyword = is_string($request->query('keyword')) ? mb_substr(trim($request->query('keyword')), 0, 50) : '';
        $type = array_key_exists((string) $request->query('type'), self::TYPES) ? (string) $request->query('type') : 'all';
        $status = array_key_exists((string) $request->query('status'), self::STATUSES) ? (string) $request->query('status') : 'all';

        $parts = collect([
            $type !== 'answer' ? $this->questions($keyword, $status) : null,
            $type !== 'question' ? $this->answers($keyword, $status) : null,
        ])->filter();

        $union = $parts->shift();
        foreach ($parts as $part) {
            $union->unionAll($part);
        }

        $posts = DB::query()
            ->fromSub($union, 'posts')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->orderBy('type')
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
            'keyword' => $keyword,
            'type' => $type,
            'status' => $status,
        ]);
    }

    /**
     * ⑥ 公開／非表示の切り替え（押した時点の状態に関係なく指定の状態にするため、二重送信しても結果は同じ）
     */
    public function hideQuestion(Question $question): RedirectResponse
    {
        return $this->setStatus($question, PostStatus::Hidden, '非表示にしました');
    }

    public function publishQuestion(Question $question): RedirectResponse
    {
        return $this->setStatus($question, PostStatus::Published, '公開にしました');
    }

    public function hideAnswer(Answer $answer): RedirectResponse
    {
        return $this->setStatus($answer, PostStatus::Hidden, '非表示にしました');
    }

    public function publishAnswer(Answer $answer): RedirectResponse
    {
        return $this->setStatus($answer, PostStatus::Published, '公開にしました');
    }

    /**
     * ⑦ 削除（物理削除。質問を削除すると、外部キーの連動でその回答・AI回答・通報も削除される。
     * ベストアンサーの回答を削除しても、質問は解決済みのまま）
     */
    public function destroyQuestion(Question $question): RedirectResponse
    {
        return $this->delete($question);
    }

    public function destroyAnswer(Answer $answer): RedirectResponse
    {
        return $this->delete($answer);
    }

    /**
     * 既に削除されている投稿を操作した場合（二重送信など）
     */
    public static function missing(): RedirectResponse
    {
        return redirect()->to(self::backUrl())->with('error', '既に削除されています');
    }

    private function setStatus(Question|Answer $post, PostStatus $status, string $message): RedirectResponse
    {
        $post->status = $status;
        $post->save();

        return redirect()->to(self::backUrl())->with('success', $message);
    }

    private function delete(Question|Answer $post): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($post) {
            $locked = $post::query()->whereKey($post->getKey())->lockForUpdate()->first();

            return $locked !== null && $locked->delete();
        });

        return $deleted
            ? redirect()->to(self::backUrl())->with('success', '削除しました')
            : self::missing();
    }

    /**
     * 操作した一覧（検索条件・ページ）へ戻る。管理画面の外の URL には戻らない
     */
    private static function backUrl(): string
    {
        $previous = url()->previous();
        $index = route('admin.posts.index');

        return $previous === $index || str_starts_with($previous, $index.'?') ? $previous : $index;
    }

    /**
     * 質問：キーワードはタイトル・本文・投稿者名
     */
    private function questions(string $keyword, string $status): Builder
    {
        return DB::table('questions')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->selectRaw("'question' as type")
            ->addSelect('questions.id', 'questions.title as content', 'users.name as user_name', 'questions.created_at', 'questions.status')
            ->when($keyword !== '', fn (Builder $query) => $this->search($query, $keyword, ['questions.title', 'questions.body', 'users.name']))
            ->when($status !== 'all', fn (Builder $query) => $query->where('questions.status', $this->statusValue($status)));
    }

    /**
     * 回答：キーワードは本文・投稿者名
     */
    private function answers(string $keyword, string $status): Builder
    {
        return DB::table('answers')
            ->join('users', 'users.id', '=', 'answers.user_id')
            ->selectRaw("'answer' as type")
            ->addSelect('answers.id', 'answers.body as content', 'users.name as user_name', 'answers.created_at', 'answers.status')
            ->when($keyword !== '', fn (Builder $query) => $this->search($query, $keyword, ['answers.body', 'users.name']))
            ->when($status !== 'all', fn (Builder $query) => $query->where('answers.status', $this->statusValue($status)));
    }

    /**
     * @param  list<string>  $columns
     */
    private function search(Builder $query, string $keyword, array $columns): void
    {
        $pattern = Like::contains($keyword);

        $query->where(function (Builder $q) use ($columns, $pattern) {
            foreach ($columns as $column) {
                $q->orWhereRaw("{$column} LIKE ? ESCAPE '".Like::ESCAPE."'", [$pattern]);
            }
        });
    }

    private function statusValue(string $status): int
    {
        return ($status === 'published' ? PostStatus::Published : PostStatus::Hidden)->value;
    }
}
