{{-- G15 ユーザー管理 --}}
<x-layouts.admin title="ユーザー管理" active="users">
    {{-- ①② 検索 --}}
    <div class="mq-panel">
        <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex align-items-end gap-2">
            <div class="mq-field-lg">
                <label class="form-label" for="keyword">キーワード</label>
                <input type="search" id="keyword" name="keyword" value="{{ $keyword }}" class="form-control"
                       maxlength="50" placeholder="ユーザー名・メールアドレスで検索">
            </div>
            <button type="submit" class="btn btn-primary px-5">検索</button>
        </form>
    </div>

    {{-- ③ ユーザー一覧 --}}
    <div class="mq-table-wrap">
        <table class="mq-table">
            <thead>
                <tr><th>ID</th><th>ユーザー名</th><th>メールアドレス</th><th>権限</th><th>回答数</th><th>状態</th><th>登録日</th><th>操作</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role->label() }}</td>
                        <td>{{ $user->answer_count }}</td>
                        <td>
                            <x-badge :variant="$user->isActive() ? 'success' : 'danger'">{{ $user->status->label() }}</x-badge>
                        </td>
                        <td>{{ $user->created_at->format('Y/m/d') }}</td>
                        <td class="text-nowrap">
                            {{-- ④ 利用停止／再開（管理者の行には出さない） --}}
                            @unless ($user->isAdmin())
                                @if ($user->isActive())
                                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="d-inline" data-submit-once>
                                        @csrf
                                        <button type="submit" class="btn btn-danger mq-btn-xs">利用停止</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.resume', $user) }}" class="d-inline" data-submit-once>
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary mq-btn-xs">利用再開</button>
                                    </form>
                                @endif
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-body-secondary py-4">該当するユーザーはいません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ⑤ ページネーション（20件ごと） --}}
    {{ $users->links('pagination.admin') }}
</x-layouts.admin>
