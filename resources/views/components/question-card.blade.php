{{--
    質問カード（G4 ⑦ の一覧）。クリックで G7 へ
    question は Question::forList() で取得したもの（answers_count・ai_answer_exists を使う）
--}}
@props(['question'])
<a href="{{ route('questions.show', $question) }}" class="mq-card mq-question-card">
    <div class="mq-badges">
        <x-badge>{{ $question->category->name }}</x-badge>
        <x-question-status :resolved="$question->is_resolved" />
        @if ($question->ai_answer_exists)
            <x-badge variant="ai">AI回答あり</x-badge>
        @endif
    </div>
    <h2 class="mq-question-card__title">{{ $question->title }}</h2>
    <p class="mq-question-card__excerpt">{{ Str::limit(Str::squish($question->body), 50) }}</p>
    <div class="mq-meta">
        <span>{{ $question->user->name }}</span>
        <x-datetime :at="$question->created_at" />
        <span>回答 {{ $question->answers_count }}</span>
    </div>
</a>
