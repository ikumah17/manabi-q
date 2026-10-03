{{--
    G21 削除確認（G7 の上にボトムシートで表示。自分の質問・回答）
    - id          : シートの id（削除ボタンの data-bs-target と対応）
    - title       : 見出し（質問を削除しますか？／回答を削除しますか？）
    - description : ② 注意書き
    - action      : 送信先（DELETE）
    - スロット    : ① 削除対象
--}}
@props(['id', 'title', 'description', 'action'])
<x-bottom-sheet :id="$id" :title="$title" :description="$description">
    <form method="POST" action="{{ $action }}" data-submit-once>
        @csrf
        @method('DELETE')

        {{-- ① 削除対象 --}}
        <div class="mq-quote mb-3">
            {{ $slot }}
        </div>

        {{-- ③ キャンセル ④ 削除する --}}
        <div class="row g-2">
            <div class="col-6">
                <button type="button" class="btn btn-outline-primary w-100" data-bs-dismiss="offcanvas">キャンセル</button>
            </div>
            <div class="col-6">
                <button type="submit" class="btn btn-danger w-100">削除する</button>
            </div>
        </div>
    </form>
</x-bottom-sheet>
