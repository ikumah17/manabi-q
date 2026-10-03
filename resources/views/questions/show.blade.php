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
        @if (Gate::allows('update', $question) || Gate::allows('report', $question))
            <div class="mq-post-actions">
                {{-- ⑪ 編集 ⑫ 削除（自分の質問のみ） --}}
                @can('update', $question)
                    <div class="ms-auto d-flex gap-2">
                        <a href="{{ route('questions.edit', $question) }}" class="btn btn-outline-primary btn-sm mq-btn-pill d-inline-flex align-items-center px-3">編集</a>
                        <button type="button" class="btn btn-outline-danger btn-sm mq-btn-pill px-3"
                                data-bs-toggle="offcanvas" data-bs-target="#delete-sheet-question">削除</button>
                    </div>
                @endcan
                {{-- ⑨ 通報する（自分の投稿には出さない） --}}
                @can('report', $question)
                    <x-report-button sheet="report-sheet-question" class="ms-auto" />
                @endcan
            </div>
        @endif
    </article>

    {{-- ③ AI回答（ある場合のみ。取得できなかった場合は質問者本人にだけその旨） --}}
    <x-ai-answer-area :question="$question" :is-owner="$isOwner" class="mb-3" />

    {{-- ④ ベストアンサー（★評価・スタンプ） --}}
    @if ($bestAnswer)
        <section class="mq-best-answer mb-3" id="answer-{{ $bestAnswer->id }}" aria-label="ベストアンサー">
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
            @can('report', $bestAnswer)
                <div class="mq-post-actions">
                    <x-report-button :sheet="'report-sheet-answer-'.$bestAnswer->id" class="ms-auto" />
                </div>
            @endcan
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

                @if (Gate::allows('chooseBest', [$question, $answer]) || Gate::allows('report', $answer) || Gate::allows('update', $answer))
                    <div class="mq-post-actions">
                        {{-- ⑥ ベストアンサーに選ぶ（質問者本人かつ未解決のときのみ。自分の回答は除く） --}}
                        @can('chooseBest', [$question, $answer])
                            <button type="button" class="btn btn-outline-primary btn-sm mq-btn-pill"
                                    data-bs-toggle="offcanvas" data-bs-target="#best-sheet-{{ $answer->id }}">ベストアンサーに選ぶ</button>
                        @endcan
                        {{-- ⑪ 編集 ⑫ 削除（自分の回答のみ。ベストアンサーには出さない） --}}
                        @can('update', $answer)
                            <div class="ms-auto d-flex gap-2">
                                <a href="{{ route('answers.edit', [$question, $answer]) }}" class="btn btn-outline-primary btn-sm mq-btn-pill d-inline-flex align-items-center px-3">編集</a>
                                <button type="button" class="btn btn-outline-danger btn-sm mq-btn-pill px-3"
                                        data-bs-toggle="offcanvas" data-bs-target="#delete-sheet-answer-{{ $answer->id }}">削除</button>
                            </div>
                        @endcan
                        {{-- ⑨ 通報する（自分の回答には出さない） --}}
                        @can('report', $answer)
                            <x-report-button :sheet="'report-sheet-answer-'.$answer->id" class="ms-auto" />
                        @endcan
                    </div>
                @endif
            </article>
        @empty
            <x-empty-state icon="pencil" sub="最初の回答を投稿してみましょう">まだ回答はありません</x-empty-state>
        @endforelse
    </div>

    {{-- ⑦ 回答する --}}
    <x-slot:fab>
        <x-fab :href="route('answers.create', $question)" icon="✎">回答する</x-fab>
    </x-slot:fab>

    <x-slot:sheets>
        {{-- G17 通報（質問・各回答ごとのボトムシート。自分の投稿は除く） --}}
        @can('report', $question)
            <x-report-sheet id="report-sheet-question" sheet-key="question" label="質問"
                            :action="route('reports.question', $question)" :post="$question" />
        @endcan
        @foreach (collect([$bestAnswer])->filter()->concat($answers) as $answer)
            @can('report', $answer)
                <x-report-sheet :id="'report-sheet-answer-'.$answer->id" :sheet-key="'answer-'.$answer->id" label="回答"
                                :action="route('reports.answer', [$question, $answer])" :post="$answer" />
            @endcan
        @endforeach

        {{-- G21 削除確認（自分の質問・自分の回答ごとのボトムシート。ベストアンサーは除く） --}}
        @can('delete', $question)
            <x-delete-sheet id="delete-sheet-question" title="質問を削除しますか？"
                            description="この質問と、ついた回答・AI回答をまとめて削除します。削除すると元に戻せません。"
                            :action="route('questions.destroy', $question)">
                <div class="mq-quote__label">削除する質問</div>
                <div class="mq-quote__title">{{ $question->title }}</div>
            </x-delete-sheet>
        @endcan
        @foreach ($answers as $answer)
            @can('delete', $answer)
                <x-delete-sheet :id="'delete-sheet-answer-'.$answer->id" title="回答を削除しますか？"
                                description="この回答を削除します。削除すると元に戻せません。"
                                :action="route('answers.destroy', [$question, $answer])">
                    <div class="mq-quote__label">削除する回答</div>
                    <div class="mq-text">{{ Str::limitChars($answer->body, 100) }}</div>
                </x-delete-sheet>
            @endcan
        @endforeach

        {{-- G9 ベストアンサー評価（回答ごとのボトムシート） --}}
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
                            <div class="mq-text">{{ Str::limitChars($answer->body, 2000) }}</div>
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
