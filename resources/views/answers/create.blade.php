{{-- G8 回答投稿 --}}
<x-layouts.mobile header="回答する" :back="route('questions.show', $question)">
    {{-- ① 質問タイトル --}}
    <div class="mq-quote mb-3">
        <div class="mq-quote__label">回答する質問</div>
        <div class="fw-bold">{{ $question->title }}</div>
    </div>

    <form method="POST" action="{{ route('answers.store', $question) }}" novalidate data-submit-once>
        @csrf

        {{-- ② 回答本文 --}}
        <div class="mb-3">
            <label for="body" class="form-label">回答本文（必須）</label>
            <textarea id="body" name="body" class="form-control" rows="9" maxlength="2000"
                      placeholder="わかりやすく回答を書いてください（2000文字以内）" required>{{ old('body') }}</textarea>
        </div>

        {{-- ③ スタンプ（獲得済みのみ選択可。未獲得はグレー表示） --}}
        <fieldset class="mb-3">
            <legend class="form-label fs-6">スタンプ（任意・獲得済みのみ選択可）</legend>
            <div class="mq-stamp-options">
                <label class="mq-stamp-option">
                    <input type="radio" name="stamp_id" value="" class="form-check-input" @checked(blank(old('stamp_id')))>
                    <span>なし</span>
                </label>
                @foreach ($stamps as $stamp)
                    @php($earned = $stamp->isEarnedWith($answerCount))
                    <label @class(['mq-stamp-option', 'is-locked' => ! $earned])>
                        <input type="radio" name="stamp_id" value="{{ $stamp->id }}" class="form-check-input"
                               @disabled(! $earned) @checked($earned && (string) old('stamp_id') === (string) $stamp->id)>
                        <span>★ {{ $stamp->name }}@unless ($earned)（未獲得）@endunless</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        {{-- ④ エラーメッセージ --}}
        <x-form-errors class="mb-3" />

        {{-- ⑥ 回答する --}}
        <button type="submit" class="btn btn-primary w-100">回答する</button>
    </form>
</x-layouts.mobile>
