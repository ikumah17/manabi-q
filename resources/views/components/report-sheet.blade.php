{{--
    G17 通報（G7 の上にボトムシートで表示）
    - id       : シートの id（通報ボタンの data-bs-target と対応）
    - sheetKey : 入力エラーで戻ったときにどのシートを開き直すかの識別子（question / answer-{id}）
    - action   : 送信先
    - label    : 通報対象の種類（質問／回答）
    - post     : 通報対象（Question または Answer。投稿者・日時・本文を表示）
--}}
@props(['id', 'sheetKey', 'action', 'label', 'post'])
@php
    $isThisSheet = old('sheet') === $sheetKey;
@endphp
<x-bottom-sheet :id="$id" title="この投稿を通報する"
                description="通報内容は管理者のみが確認します。通報者の名前が相手に知られることはありません。"
                :open="$isThisSheet && $errors->getBag('report')->isNotEmpty()">
    <form method="POST" action="{{ $action }}" novalidate data-submit-once>
        @csrf
        <input type="hidden" name="sheet" value="{{ $sheetKey }}">

        {{-- ① 通報対象 --}}
        <div class="mq-quote mb-3">
            <div class="mq-meta mq-meta--dot mb-1">
                <span>{{ $label }}：{{ $post->user->name }}</span>
                <x-datetime :at="$post->created_at" />
            </div>
            <div class="mq-text">{{ Str::limit($post->body, 200) }}</div>
        </div>

        {{-- ② 通報理由（必須） --}}
        <fieldset class="mb-3">
            <legend class="form-label fs-6">通報理由（必須）</legend>
            @foreach (\App\Enums\ReportReason::cases() as $reason)
                <div class="form-check mb-1">
                    <input type="radio" class="form-check-input" name="reason" value="{{ $reason->value }}"
                           id="{{ $id }}-reason-{{ $reason->value }}"
                           @checked($isThisSheet && (string) old('reason') === (string) $reason->value)>
                    <label class="form-check-label" for="{{ $id }}-reason-{{ $reason->value }}">{{ $reason->label() }}</label>
                </div>
            @endforeach
        </fieldset>

        {{-- ③ 詳細（任意） --}}
        <div class="mb-3">
            <label for="{{ $id }}-detail" class="form-label">詳細（任意）</label>
            <textarea id="{{ $id }}-detail" name="detail" class="form-control" rows="3" maxlength="500"
                      placeholder="具体的な内容があれば入力してください（500文字以内）">{{ $isThisSheet ? old('detail') : '' }}</textarea>
        </div>

        {{-- ④ エラーメッセージ（未選択・重複通報など） --}}
        @if ($isThisSheet)
            <x-form-errors bag="report" class="mb-3" />
        @endif

        {{-- ⑤ キャンセル ⑥ 通報する --}}
        <div class="row g-2">
            <div class="col-6">
                <button type="button" class="btn btn-outline-primary w-100" data-bs-dismiss="offcanvas">キャンセル</button>
            </div>
            <div class="col-6">
                <button type="submit" class="btn btn-danger w-100">通報する</button>
            </div>
        </div>
    </form>
</x-bottom-sheet>
