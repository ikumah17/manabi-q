{{-- G4 ホーム（質問一覧） --}}
<x-layouts.mobile tab="home" title="ホーム">
    {{-- ②〜⑤ 検索条件 --}}
    <form method="GET" action="{{ route('home') }}" class="mb-3" role="search">
        <div class="d-flex gap-2 mb-2">
            <label for="keyword" class="visually-hidden">キーワード</label>
            <input type="search" id="keyword" name="keyword" value="{{ $keyword }}" class="form-control"
                   maxlength="50" placeholder="キーワードで検索">
            <button type="submit" class="btn btn-primary px-4">検索</button>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label for="category" class="visually-hidden">カテゴリ</label>
                <select id="category" name="category" class="form-select">
                    <option value="">カテゴリ：すべて</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label for="sort" class="visually-hidden">並び替え</label>
                <select id="sort" name="sort" class="form-select">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- ⑦ 質問一覧 --}}
    <div class="mq-stack">
        @forelse ($questions as $question)
            <x-question-card :question="$question" />
        @empty
            <p class="mq-empty">該当する質問はありません</p>
        @endforelse
    </div>

    {{-- ⑧ ページネーション（10件ごと） --}}
    {{ $questions->links() }}

    {{-- ⑨⑩ 回答者ランキング TOP5・ランキングをもっと見る（G10 へ） --}}
    <x-ranking-top :entries="$topRankers" class="mt-3" />

    {{-- ⑥ 質問を投稿する --}}
    <x-slot:fab>
        <x-fab :href="route('questions.create')">質問する</x-fab>
    </x-slot:fab>
</x-layouts.mobile>
