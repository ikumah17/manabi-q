{{-- 開発用：管理画面の共通部品の表示確認（APP_ENV=local のときだけ /_dev/admin で表示） --}}
@php
    $paginator = new Illuminate\Pagination\LengthAwarePaginator(range(1, 20), 60, 20, 1, ['path' => url('/_dev/admin')]);
    $rows = [
        [1284, '質問', 'Laravelのマイグレーションでエラーが出ます', '山田 太郎', '2026/09/30 13:05', true],
        [3960, '回答', '教科書の第4章 例題3 がほぼ同じ問題なので…', '文学 三子', '2026/10/01 11:45', true],
        [1283, '質問', '不適切な内容の質問', '匿名 太郎', '2026/09/29 22:10', false],
    ];
@endphp
<x-layouts.admin title="投稿管理" active="posts">
    <div class="mq-panel">
        <form class="d-flex align-items-end gap-2">
            <div style="width: 420px">
                <label class="form-label" for="keyword">キーワード</label>
                <input type="text" id="keyword" class="form-control" placeholder="タイトル・本文・投稿者で検索">
            </div>
            <div style="width: 160px">
                <label class="form-label" for="type">種別</label>
                <select id="type" class="form-select"><option>すべて</option></select>
            </div>
            <div style="width: 160px">
                <label class="form-label" for="state">状態</label>
                <select id="state" class="form-select"><option>すべて</option></select>
            </div>
            <button type="button" class="btn btn-primary px-5">検索</button>
        </form>
    </div>

    <div class="mq-error mb-3">同じ名前のカテゴリが既に存在します</div>

    <div class="mq-table-wrap">
        <table class="mq-table">
            <thead>
                <tr><th>ID</th><th>種別</th><th>内容</th><th>投稿者</th><th>投稿日時</th><th>状態</th><th>操作</th></tr>
            </thead>
            <tbody>
                @foreach ($rows as [$id, $type, $content, $user, $date, $published])
                    <tr>
                        <td>{{ $id }}</td>
                        <td><x-badge :variant="$type === '質問' ? 'primary' : 'success'">{{ $type }}</x-badge></td>
                        <td>{{ $content }}</td>
                        <td>{{ $user }}</td>
                        <td>{{ $date }}</td>
                        <td><x-badge :variant="$published ? 'success' : 'muted'">{{ $published ? '公開' : '非表示' }}</x-badge></td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-outline-primary mq-btn-xs">{{ $published ? '非表示' : '公開' }}</button>
                            <form class="d-inline" data-confirm="この投稿を削除しますか？" onsubmit="return false">
                                <button type="submit" class="btn btn-danger mq-btn-xs">削除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $paginator->links('pagination.admin') }}
</x-layouts.admin>
