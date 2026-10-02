{{--
    確認モーダル（ブラウザ標準の confirm() の代わり。G18 対象を非表示 など）
    開くボタンには data-bs-toggle="modal" data-bs-target="#{id}" と、
    data-confirm-action（送信先 URL）・data-confirm-message（対象の内容。テキストとして表示）を付ける
--}}
@props(['id', 'title', 'description', 'confirmLabel', 'confirmClass' => 'btn-danger'])
<div class="modal fade mq-confirm-modal" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true" data-confirm-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="" data-confirm-form>
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-6 fw-bold" id="{{ $id }}-title">{{ $title }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">{{ $description }}</p>
                    <div class="mq-confirm-modal__target" data-confirm-target></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary mq-btn-xs" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn {{ $confirmClass }} mq-btn-xs" data-confirm-submit>{{ $confirmLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
