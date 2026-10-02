{{--
    AI即時回答の欄（G6 ③④・G7 ③）
    - AI回答がある場合：回答と注意書きを表示（全員）
    - AI回答を希望したが無い場合：質問者本人にだけ、取得できなかった旨を表示
      （失敗直後は session('ai_error') の文言、あとから開いた場合は既定の文言）
    - それ以外：何も表示しない（設計：「AI回答がある場合のみ表示する」）
--}}
@props(['question', 'isOwner' => false, 'note' => '※ AIの回答は誤りを含む場合があります。'])
@if ($question->aiAnswer)
    <x-ai-answer :ai-answer="$question->aiAnswer" :note="$note" {{ $attributes }} />
@elseif ($question->use_ai && $isOwner)
    <section {{ $attributes->class('mq-ai-answer') }} aria-label="AIの即時回答">
        <div class="mq-ai-answer__head">
            <span class="mq-ai-answer__label">AI</span>
            <h2 class="mq-ai-answer__title">AIの即時回答</h2>
        </div>
        <p class="mq-ai-answer__unavailable" role="status">{{ session('ai_error') ?? \App\Services\AiAnswer\AiAnswerResult::FAILED_MESSAGE }}</p>
    </section>
@endif
