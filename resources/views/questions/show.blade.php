{{-- G7 質問詳細（G9 ベストアンサー評価をボトムシートで表示） --}}
<x-layouts.mobile header="質問詳細" :back="route('home')" tab="home">
    {{-- G9 の入力エラー（評価未選択など） --}}
    <x-form-errors bag="best" class="mb-3" />

    {{-- ①② 質問情報・本文 --}}
    <article class="mq-card mb-3">
        <div class="mq-badges">
            <x-badge>{{ $question->category->name }}</x-badge>
            <x-question-status :resolved="$question->is_resolved" />
        </div>
        <h1 class="mq-question-title">{{ $question->title }}</h1>
        <div class="mq-meta mq-meta--dot mb-2">
            <span>{{ $question->user->name }}@if ($isOwner)（あなた）@endif</span>
            <x-datetime :at="$question->created_at" />
            <span>回答 {{ $answerCount }}件</span>
        </div>
        <div class="mq-text">{{ $question->body }}</div>
        {{-- ⑨ 通報：Phase 7 で G17 と合わせて実装 --}}
    </article>

    {{-- ③ AI回答（ある場合のみ） --}}
    @if ($question->aiAnswer)
        <x-ai-answer :ai-answer="$question->aiAnswer" class="mb-3" />
    @endif

    {{-- ④ ベストアンサー（★評価・スタンプ） --}}
    @if ($bestAnswer)
        <section class="mq-best-answer mb-3" aria-label="ベストアンサー">
            <div class="d-flex align-items-center gap-2 mb-1">
                <x-badge variant="best">ベストアンサー</x-badge>
                <x-stars :rating="$bestAnswer->rating" />
            </div>
            <div class="mq-meta mq-meta--dot mb-1">
                <span>{{ $bestAnswer->user->name }}</span>
                <x-datetime :at="$bestAnswer->created_at" />
            </div>
            <div class="mq-text">{{ $bestAnswer->body }}</div>
            @if ($bestAnswer->stamp)
                <x-stamp-chip :stamp="$bestAnswer->stamp" class="mt-2" />
            @endif
        </section>
    @endif

    {{-- ⑤ 回答一覧（投稿日時順。ベストアンサーは④に表示） --}}
    <h2 class="mq-section-title">回答一覧（{{ $answers->count() }}件）</h2>
    <div class="mq-stack">
        @forelse ($answers as $answer)
            <article class="mq-card" id="answer-{{ $answer->id }}">
                <div class="mq-meta mq-meta--dot mb-1">
                    <span>{{ $answer->user->name }}</span>
                    <x-datetime :at="$answer->created_at" />
                </div>
                <div class="mq-text">{{ $answer->body }}</div>
                @if ($answer->stamp)
                    <x-stamp-chip :stamp="$answer->stamp" class="mt-2" />
                @endif

                {{-- ⑥ ベストアンサーに選ぶ（質問者本人かつ未解決のときのみ。自分の回答は除く） --}}
                @can('chooseBest', [$question, $answer])
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm mq-btn-pill"
                                data-bs-toggle="offcanvas" data-bs-target="#best-sheet-{{ $answer->id }}">ベストアンサーに選ぶ</button>
                    </div>
                @endcan
            </article>
        @empty
            <p class="mq-empty">まだ回答はありません</p>
        @endforelse
    </div>

    {{-- ⑦ 回答する --}}
    <x-slot:fab>
        <x-fab :href="route('answers.create', $question)" icon="✎">回答する</x-fab>
    </x-slot:fab>

    {{-- G9 ベストアンサー評価（回答ごとのボトムシート） --}}
    <x-slot:sheets>
        @foreach ($answers as $answer)
            @can('chooseBest', [$question, $answer])
                <x-bottom-sheet :id="'best-sheet-'.$answer->id" title="ベストアンサーを評価する"
                                description="この回答をベストアンサーに選び、★1〜5で評価してください。決定後は変更できません。"
                                :open="(string) old('sheet') === (string) $answer->id">
                    <form method="POST" action="{{ route('answers.best', [$question, $answer]) }}" data-submit-once>
                        @csrf
                        <input type="hidden" name="sheet" value="{{ $answer->id }}">

                        {{-- ① 対象回答 --}}
                        <div class="mq-quote mb-3">
                            <div class="mq-meta mq-meta--dot mb-1">
                                <span>{{ $answer->user->name }}</span>
                                <x-datetime :at="$answer->created_at" />
                            </div>
                            <div class="mq-text">{{ Str::limit($answer->body, 200) }}</div>
                        </div>

                        {{-- ② 評価（★1〜5・必須） --}}
                        <fieldset class="mq-rating mb-3" data-rating>
                            <legend class="visually-hidden">評価（★1〜5）</legend>
                            <div class="mq-rating__stars">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" class="mq-rating__input" name="rating" value="{{ $i }}"
                                           id="rating-{{ $answer->id }}-{{ $i }}"
                                           @checked((string) old('sheet') === (string) $answer->id && (string) old('rating') === (string) $i)>
                                    <label for="rating-{{ $answer->id }}-{{ $i }}" class="mq-rating__star" title="★{{ $i }}">★<span class="visually-hidden">{{ $i }}</span></label>
                                @endfor
                            </div>
                            <div class="mq-rating__value" data-rating-value>評価：− / 5</div>
                        </fieldset>

                        {{-- ③ キャンセル ④ 決定する --}}
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100" data-bs-dismiss="offcanvas">キャンセル</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" class="btn btn-primary w-100" data-rating-submit>決定する</button>
                            </div>
                        </div>
                    </form>
                </x-bottom-sheet>
            @endcan
        @endforeach
    </x-slot:sheets>
</x-layouts.mobile>
