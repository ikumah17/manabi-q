{{--
    ボトムシート（G9 ベストアンサー評価・G17 通報。G7 の上に表示する）
    - id          : 開くボタンの data-bs-target="#id" と対応させる
    - title       : シートの見出し
    - description : 見出しの下の説明文
    - open        : true なら表示時に自動で開く（入力エラーで戻ってきたとき）
    キャンセルボタンには data-bs-dismiss="offcanvas" を付ける
--}}
@props([
    'id',
    'title',
    'description' => null,
    'open' => false,
])
<div {{ $attributes->class('offcanvas offcanvas-bottom mq-sheet') }}
     tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $id }}-title"
     @if ($open) data-mq-autoshow @endif>
    <div class="mq-sheet__handle" aria-hidden="true"></div>
    <div class="offcanvas-header d-block">
        <h2 class="mq-sheet__title" id="{{ $id }}-title">{{ $title }}</h2>
        @if ($description)
            <p class="mq-sheet__description">{{ $description }}</p>
        @endif
    </div>
    <div class="offcanvas-body">
        {{ $slot }}
    </div>
</div>
