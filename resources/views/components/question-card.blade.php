{{--
    質問カード（G4 ⑦・G11 ④ の一覧）。クリックで G7 へ
    question は Question::forList() で取得したもの（answers_count・ai_answer_exists を使う）
    - compact : G11 用（投稿者・本文抜粋を出さず「日付・回答 n」だけ）
    - at      : 表示する日時（G11「自分の回答」では自分の回答の日時）
    - href    : リンク先（G11「自分の回答」では該当する回答の位置）
--}}
@props(['question', 'compact' => false, 'at' => null, 'href' => null])
<a href="{{ $href ?? route('questions.show', $question) }}" class="mq-card mq-question-card">
    <div class="mq-badges">
        <x-badge>{{ $question->category->name }}</x-badge>
        <x-question-status :resolved="$question->is_resolved" />
        @if ($question->ai_answer_exists)
            <x-badge variant="ai">AI回答あり</x-badge>
        @endif
    </div>
    <h2 class="mq-question-card__title">{{ $question->title }}</h2>
    @if ($compact)
        <div class="mq-meta mq-meta--dot">
            <x-datetime :at="$at ?? $question->created_at" :date-only="true" />
            <span>回答 {{ $question->answers_count }}</span>
        </div>
    @else
        <p class="mq-question-card__excerpt">{{ Str::limitChars(Str::squish($question->body), 50) }}</p>
        <div class="mq-meta">
            <span>{{ $question->user->name }}</span>
            <x-datetime :at="$question->created_at" />
            <span>回答 {{ $question->answers_count }}</span>
        </div>
    @endif
</a>
