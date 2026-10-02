{{-- G6 質問投稿完了 --}}
<x-layouts.mobile header="投稿完了">
    {{-- ① 完了メッセージ --}}
    <div class="mq-flash mq-flash--success mb-3" role="status">✓ 質問を投稿しました</div>

    {{-- ② 投稿内容 --}}
    <div class="mq-card mb-3">
        <div class="mq-badges">
            <x-badge>{{ $question->category->name }}</x-badge>
            <x-question-status :resolved="$question->is_resolved" />
        </div>
        <h1 class="mq-question-title">{{ $question->title }}</h1>
        <div class="mq-text">{{ $question->body }}</div>
    </div>

    {{-- ③④ AI回答・注意書き（AI即時回答を希望し、回答がある場合のみ） --}}
    @if ($question->use_ai && $question->aiAnswer)
        <x-ai-answer :ai-answer="$question->aiAnswer" class="mb-3"
                     note="※ AIの回答は誤りを含む場合があります。参考情報としてご利用ください。" />
    @endif

    {{-- ⑤ 質問詳細を見る ⑥ ホームへ戻る --}}
    <div class="d-grid gap-3">
        <a href="{{ route('questions.show', $question) }}" class="btn btn-primary">質問詳細を見る</a>
        <a href="{{ route('home') }}" class="btn btn-outline-primary">ホームへ戻る</a>
    </div>
</x-layouts.mobile>
