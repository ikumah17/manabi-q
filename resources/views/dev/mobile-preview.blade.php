{{-- 開発用：一般画面の共通部品の表示確認（APP_ENV=local のときだけ /_dev/mobile で表示） --}}
@php
    $paginator = new Illuminate\Pagination\LengthAwarePaginator(range(1, 10), 30, 10, 1, ['path' => url('/_dev/mobile')]);
@endphp
<x-layouts.mobile tab="home" title="共通部品の確認（一般）">
    <div class="mq-error mb-3" role="alert">タイトルを入力してください</div>
    <div class="mq-flash mq-flash--success mb-3" role="status">✓ 質問を投稿しました</div>

    <div class="d-flex gap-2 mb-3">
        <input type="text" class="form-control" placeholder="キーワードで検索">
        <button type="button" class="btn btn-primary px-4">検索</button>
    </div>

    @foreach ([['授業', '未解決', true], ['学校生活', '解決済み', false], ['課題・レポート', '未解決', true]] as [$category, $state, $ai])
        <div class="mq-card mb-3">
            <div class="d-flex gap-1 mb-1">
                <x-badge>{{ $category }}</x-badge>
                <x-badge :variant="$state === '解決済み' ? 'success' : 'danger'">{{ $state }}</x-badge>
                @if ($ai)
                    <x-badge variant="ai">AI回答あり</x-badge>
                @endif
            </div>
            <div class="fw-bold">線形代数のレポートで固有値の求め方がわかりません</div>
            <div class="text-body-secondary small">情報 花子　10/01 09:12　回答 3</div>
        </div>
    @endforeach

    {{ $paginator->links() }}

    <div class="d-grid gap-2 mt-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#preview-sheet">ボトムシートを開く</button>
        <button type="button" class="btn btn-outline-primary">ホームへ戻る</button>
    </div>

    <x-slot:fab>
        <x-fab href="#">質問する</x-fab>
    </x-slot:fab>

    <x-slot:sheets>
        <x-bottom-sheet id="preview-sheet" title="この投稿を通報する" description="通報内容は管理者のみが確認します。通報者の名前が相手に知られることはありません。">
            <div class="mq-error mb-3">通報理由を選択してください</div>
            <div class="row g-2">
                <div class="col"><button type="button" class="btn btn-outline-primary w-100" data-bs-dismiss="offcanvas">キャンセル</button></div>
                <div class="col"><button type="button" class="btn btn-danger w-100">通報する</button></div>
            </div>
        </x-bottom-sheet>
    </x-slot:sheets>
</x-layouts.mobile>
