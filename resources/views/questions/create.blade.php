{{-- G5 質問投稿 --}}
<x-layouts.mobile header="質問を投稿する" :back="route('home')">
    <form method="POST" action="{{ route('questions.store') }}" novalidate data-submit-once>
        @csrf

        {{-- ① タイトル --}}
        <div class="mb-4">
            <label for="title" class="form-label">タイトル（必須）</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" @class(['form-control', 'is-invalid' => $errors->has('title')])
                   maxlength="100" placeholder="質問内容を簡潔に（100文字以内）" required
                   @error('title') aria-invalid="true" @enderror>
        </div>

        {{-- ② カテゴリ --}}
        <div class="mb-4">
            <label for="category_id" class="form-label">カテゴリ（必須）</label>
            <select id="category_id" name="category_id" @class(['form-select', 'is-invalid' => $errors->has('category_id')]) required
                    @error('category_id') aria-invalid="true" @enderror>
                <option value="">選択してください</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ③ 本文 --}}
        <div class="mb-4">
            <label for="body" class="form-label">本文（必須）</label>
            <textarea id="body" name="body" @class(['form-control', 'is-invalid' => $errors->has('body')]) rows="10" maxlength="2000"
                      placeholder="困っていること・試したことを具体的に書いてください（2000文字以内）" required
                      @error('body') aria-invalid="true" @enderror>{{ old('body') }}</textarea>
        </div>

        {{-- ④ AI即時回答（初期表示 ON） --}}
        <div class="mq-ai-option mb-4">
            <input type="hidden" name="use_ai" value="0">
            <input type="checkbox" id="use_ai" name="use_ai" value="1" class="form-check-input mq-ai-option__check"
                   @checked(old('use_ai', '1') === '1')>
            <label for="use_ai" class="mq-ai-option__label">
                <span class="mq-ai-option__title"><x-icon name="sparkle" class="mq-ai-option__icon" />AIの即時回答を受け取る（Gemini）</span>
                <span class="mq-ai-option__text">投稿と同時にAIが回答案を作成します。人による回答も引き続き受け付けます。</span>
            </label>
        </div>

        {{-- ⑤ エラーメッセージ --}}
        <x-form-errors class="mb-4" />

        {{-- ⑦ 投稿する --}}
        <button type="submit" class="btn btn-primary w-100 mq-btn-tall">投稿する</button>
    </form>
</x-layouts.mobile>
