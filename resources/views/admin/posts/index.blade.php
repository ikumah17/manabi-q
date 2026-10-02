{{-- G14 投稿管理 --}}
<x-layouts.admin title="投稿管理" active="posts">
    {{-- ①〜④ 検索条件 --}}
    <div class="mq-panel">
        <form method="GET" action="{{ route('admin.posts.index') }}" class="d-flex align-items-end gap-2">
            <div class="mq-field-lg">
                <label class="form-label" for="keyword">キーワード</label>
                <input type="search" id="keyword" name="keyword" value="{{ $keyword }}" class="form-control"
                       maxlength="50" placeholder="タイトル・本文・投稿者で検索">
            </div>
            <div class="mq-field-sm">
                <label class="form-label" for="type">種別</label>
                <select id="type" name="type" class="form-select">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mq-field-sm">
                <label class="form-label" for="status">状態</label>
                <select id="status" name="status" class="form-select">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary px-5">検索</button>
        </form>
    </div>

    {{-- ⑤ 投稿一覧 --}}
    <div class="mq-table-wrap">
        <table class="mq-table">
            <thead>
                <tr><th>ID</th><th>種別</th><th>内容</th><th>投稿者</th><th>投稿日時</th><th>状態</th><th>操作</th></tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    @php
                        $isQuestion = $post->type === 'question';
                        $published = (int) $post->status === \App\Enums\PostStatus::Published->value;
                        $content = Str::squish($post->content);
                    @endphp
                    <tr>
                        <td>{{ $post->id }}</td>
                        <td><x-badge :variant="$isQuestion ? 'primary' : 'success'">{{ $isQuestion ? '質問' : '回答' }}</x-badge></td>
                        <td class="mq-cell-content" title="{{ $content }}">{{ Str::limitChars($content, 40) }}</td>
                        <td class="text-nowrap">{{ $post->user_name }}</td>
                        <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($post->created_at)->format('Y/m/d H:i') }}</td>
                        <td><x-badge :variant="$published ? 'success' : 'muted'">{{ $published ? '公開' : '非表示' }}</x-badge></td>
                        <td class="text-nowrap">{{-- ⑥⑦ 操作は 8-5 で追加 --}}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-body-secondary py-4">該当する投稿はありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ⑧ ページネーション（20件ごと） --}}
    {{ $posts->links('pagination.admin') }}
</x-layouts.admin>
