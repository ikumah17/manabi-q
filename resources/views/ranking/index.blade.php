{{-- G10 回答者ランキング --}}
<x-layouts.mobile header="回答者ランキング" tab="ranking">
    <p class="mq-page-lead">回答数の多い順に表示しています。回答数に応じてスタンプ（10・50・100回答）を獲得できます。</p>

    {{-- ② あなたの順位 --}}
    <section class="mq-my-rank mb-4" aria-label="あなたの順位">
        <span class="mq-my-rank__label">あなたの順位</span>
        @if ($mine)
            <span class="mq-my-rank__rank">{{ $mine->rank }}位</span>
            <span class="mq-my-rank__detail">回答数 {{ $mine->answerCount }}件<br>ベストアンサー {{ $mine->bestAnswerCount }}件</span>
        @else
            <span class="mq-my-rank__rank">−</span>
            <span class="mq-my-rank__detail">まだ回答がありません</span>
        @endif
    </section>

    {{-- ① ランキング一覧 --}}
    @if ($entries->isEmpty())
        <x-empty-state icon="trophy" sub="回答するとランキングに表示されます">まだランキングはありません</x-empty-state>
    @else
        <ol class="mq-ranking-list" aria-label="ランキング一覧">
            @foreach ($entries as $entry)
                <li @class(['mq-ranking-list__item', 'is-mine' => $entry->userId === $userId])
                    @if ($entry->userId === $userId) aria-current="true" @endif>
                    <x-rank-badge :rank="$entry->rank" />
                    <div class="mq-ranking-list__body">
                        <div class="mq-ranking-list__name">{{ $entry->name }}</div>
                        <div class="mq-ranking-list__sub">
                            <span>ベストアンサー {{ $entry->bestAnswerCount }}件</span>
                            <x-stamp-levels :stamps="$entry->earnedStamps" class="mq-stamp-levels" />
                        </div>
                    </div>
                    <div class="mq-ranking-list__count">
                        <span class="mq-ranking-list__number">{{ $entry->answerCount }}</span>
                        <span class="mq-ranking-list__unit">回答</span>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    {{-- ③ ページネーション（20件ごと） --}}
    {{ $entries->links() }}
</x-layouts.mobile>
