<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * G15 ユーザー管理（ユーザーの削除は設計書に無いため作らない）
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = is_string($request->query('keyword')) ? mb_substr(trim($request->query('keyword')), 0, 50) : '';

        $users = User::query()
            // 回答数はランキングと同じ数え方（回答と質問の両方が公開中のもの）
            ->withCount(['answers as answer_count' => fn ($query) => $query->countable()])
            ->when($keyword !== '', function ($query) use ($keyword) {
                $pattern = Like::contains($keyword);
                $query->where(fn ($q) => $q->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("email LIKE ? ESCAPE '!'", [$pattern]));
            })
            ->orderBy('id')
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'keyword' => $keyword]);
    }

    /**
     * ④ 利用停止（次のアクセスで強制ログアウトされる。管理者は対象外）
     */
    public function suspend(User $user): RedirectResponse
    {
        return $this->changeStatus($user, UserStatus::Suspended, '利用を停止しました');
    }

    /**
     * ④ 利用再開
     */
    public function resume(User $user): RedirectResponse
    {
        return $this->changeStatus($user, UserStatus::Active, '利用を再開しました');
    }

    private function changeStatus(User $user, UserStatus $status, string $message): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $user->status = $status;
        $user->save();

        $previous = url()->previous();
        $index = route('admin.users.index');
        $url = $previous === $index || str_starts_with($previous, $index.'?') ? $previous : $index;

        return redirect()->to($url)->with('success', $message);
    }
}
