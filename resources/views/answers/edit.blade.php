{{-- G20 回答編集（本文のみ。添付スタンプは表示だけで変更しない） --}}
<x-layouts.mobile header="回答を編集" :back="route('questions.show', $question)">
    {{-- ① 質問タイトル --}}
    <div class="mq-quote mb-4">
        <div class="mq-quote__label">回答した質問</div>
        <div class="mq-quote__title">{{ $question->title }}</div>
    </div>

    <form method="POST" action="{{ route('answers.update', [$question, $answer]) }}" novalidate data-submit-once>
        @csrf
        @method('PUT')

        {{-- ② 回答本文 --}}
        <div class="mb-4">
            <label for="body" class="form-label">回答本文（必須）</label>
            <textarea id="body" name="body" @class(['form-control', 'is-invalid' => $errors->has('body')]) rows="9" maxlength="2000"
                      placeholder="わかりやすく回答を書いてください（2000文字以内）" required
                      @error('body') aria-invalid="true" @enderror>{{ old('body', $answer->body) }}</textarea>
        </div>

        {{-- ③ 添付スタンプ（ある場合のみ。変更不可） --}}
        @if ($answer->stamp)
            <div class="mb-4">
                <div class="form-label">添付スタンプ（変更できません）</div>
                <x-stamp-chip :stamp="$answer->stamp" />
            </div>
        @endif

        {{-- ④ エラーメッセージ --}}
        <x-form-errors class="mb-4" />

        {{-- ⑥ 更新する --}}
        <button type="submit" class="btn btn-primary w-100 mq-btn-tall">更新する</button>
    </form>
</x-layouts.mobile>
