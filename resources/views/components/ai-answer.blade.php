{{--
    AI の即時回答（G6 ③④・G7 ③）
    note : 注意書き（G6 は「参考情報としてご利用ください。」まで、G7 は短い文）
--}}
@props(['aiAnswer', 'note' => '※ AIの回答は誤りを含む場合があります。'])
<section {{ $attributes->class('mq-ai-answer') }} aria-label="AIの即時回答">
    <div class="mq-ai-answer__head">
        <span class="mq-ai-answer__label"><x-icon name="sparkle" />AI</span>
        <h2 class="mq-ai-answer__title">AIの即時回答</h2>
    </div>
    <div class="mq-ai-answer__body mq-text">{{ $aiAnswer->body }}</div>
    <p class="mq-ai-answer__note">{{ $note }}</p>
</section>
