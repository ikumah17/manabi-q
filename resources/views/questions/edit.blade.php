{{-- G19 質問編集（タイトル・カテゴリ・本文のみ。AI回答希望は変更しない） --}}
<x-layouts.mobile header="質問を編集" :back="route('questions.show', $question)">
    <form method="POST" action="{{ route('questions.update', $question) }}" novalidate data-submit-once>
        @csrf
        @method('PUT')

        {{-- ① タイトル --}}
        <div class="mb-4">
            <label for="title" class="form-label">タイトル（必須）</label>
            <input type="text" id="title" name="title" value="{{ old('title', $question->title) }}" @class(['form-control', 'is-invalid' => $errors->has('title')])
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
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $question->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ③ 本文 --}}
        <div class="mb-4">
            <label for="body" class="form-label">本文（必須）</label>
            <textarea id="body" name="body" @class(['form-control', 'is-invalid' => $errors->has('body')]) rows="10" maxlength="2000"
                      placeholder="困っていること・試したことを具体的に書いてください（2000文字以内）" required
                      @error('body') aria-invalid="true" @enderror>{{ old('body', $question->body) }}</textarea>
        </div>

        {{-- ④ エラーメッセージ --}}
        <x-form-errors class="mb-4" />

        {{-- ⑥ 更新する --}}
        <button type="submit" class="btn btn-primary w-100 mq-btn-tall">更新する</button>
    </form>
</x-layouts.mobile>
