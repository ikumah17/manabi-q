{{-- G4 ⑨ 回答者ランキング TOP5・⑩ ランキングへ --}}
@props(['entries'])
<section {{ $attributes->class('mq-card mq-ranking-top') }} aria-labelledby="ranking-top-title">
    <h2 class="mq-ranking-top__title" id="ranking-top-title"><span aria-hidden="true">🏆</span> 回答者ランキング TOP5</h2>
    @forelse ($entries as $entry)
        <div class="mq-ranking-top__row">
            <span @class(['mq-ranking-top__rank', 'is-first' => $entry->rank === 1])>{{ $entry->rank }}位</span>
            <span class="mq-ranking-top__name">{{ $entry->name }}</span>
            <span class="mq-ranking-top__count">{{ $entry->answerCount }}件</span>
        </div>
    @empty
        <p class="text-body-secondary small mb-1">まだランキングはありません</p>
    @endforelse
    <a href="{{ route('ranking') }}" class="mq-ranking-top__more">ランキングをもっと見る</a>
</section>
