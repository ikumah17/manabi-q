{{-- G11 マイページ --}}
<x-layouts.mobile header="マイページ" tab="mypage">
    {{-- ① ユーザー情報（統計は「ラベル 値」の順。見た目は値を上に出す） --}}
    <section class="mq-card mb-4" aria-label="ユーザー情報">
        <div class="mq-profile">
            <span class="mq-profile__avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
            <div class="mq-profile__body">
                <div class="mq-profile__name">{{ $user->name }}</div>
                <div class="mq-profile__email">{{ $user->email }}</div>
            </div>
        </div>
        <dl class="mq-stats">
            @foreach (['質問' => $questionCount, '回答' => $answerCount, 'BA' => $bestAnswerCount, '順位' => $rank !== null ? $rank.'位' : '−'] as $label => $value)
                <div class="mq-stat">
                    <dt class="mq-stat__label">{{ $label }}</dt>
                    <dd class="mq-stat__value">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
        {{-- ① ベストアンサー評価の平均（★n.n・n件。評価が無ければ「—」） --}}
        <dl class="d-flex justify-content-between align-items-baseline gap-2 mt-3 mb-0">
            <dt class="mq-stat__label">ベストアンサー評価の平均</dt>
            <dd class="mq-stat__value">
                @if ($averageRating !== null)
                    <span class="mq-stamp-chip__star" aria-hidden="true">★</span>{{ number_format($averageRating, 1) }}・{{ $ratedCount }}件
                @else
                    —
                @endif
            </dd>
        </dl>
        {{-- ⑤ プロフィール編集 --}}
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary w-100 mt-3 mq-btn-pill">プロフィール編集</a>
    </section>

    {{-- ② 獲得スタンプ（獲得済みは★、未獲得は鍵のアイコンと「あと n 回答」の進み具合） --}}
    <section class="mq-card mb-4" aria-labelledby="stamps-title">
        <h2 class="mq-card-title" id="stamps-title">獲得スタンプ</h2>
        <div class="mq-stamp-tiles">
            @foreach ($stamps as $item)
                @php($required = $item['stamp']->required_count)
                <div @class(['mq-stamp-tile', 'is-earned' => $item['earned']])>
                    @if ($item['earned'])
                        <span class="mq-stamp-tile__icon" aria-hidden="true">★</span>
                    @else
                        <span class="mq-stamp-tile__icon"><x-icon name="lock" /><span class="visually-hidden">未獲得</span></span>
                    @endif
                    <span class="mq-stamp-tile__name">{{ $item['stamp']->name }}</span>
                    <span class="mq-stamp-tile__sub">{{ $item['earned'] ? '獲得済み' : 'あと'.$item['remaining'].'回答' }}</span>
                    <span class="mq-stamp-tile__bar" aria-hidden="true"><span style="width: {{ $item['earned'] ? 100 : (int) floor(($required - $item['remaining']) / max($required, 1) * 100) }}%"></span></span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ③ タブ（自分の質問／自分の回答） --}}
    <nav class="mq-tabs mb-3" aria-label="投稿の種類">
        <a href="{{ route('mypage') }}" @class(['mq-tabs__item', 'is-active' => $tab === 'questions'])
           @if ($tab === 'questions') aria-current="page" @endif>自分の質問（{{ $questionCount }}）</a>
        <a href="{{ route('mypage', ['tab' => 'answers']) }}" @class(['mq-tabs__item', 'is-active' => $tab === 'answers'])
           @if ($tab === 'answers') aria-current="page" @endif>自分の回答（{{ $answerCount }}）</a>
    </nav>

    {{-- ④ 投稿一覧（クリックで G7） --}}
    <div class="mq-stack">
        @forelse ($list as $item)
            @if ($tab === 'answers')
                <x-question-card :question="$item->question" :href="route('questions.show', $item->question).'#answer-'.$item->id"
                                 :at="$item->created_at" :compact="true" />
            @else
                <x-question-card :question="$item" :compact="true" />
            @endif
        @empty
            @if ($tab === 'answers')
                <x-empty-state icon="pencil" sub="回答するとここに表示されます">まだ回答はありません</x-empty-state>
            @else
                <x-empty-state icon="chat-question" sub="分からないことを質問してみましょう">まだ質問はありません</x-empty-state>
            @endif
        @endforelse
    </div>

    {{ $list->links() }}

    {{-- ⑥ ログアウト --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-outline-danger bg-white w-100 mq-logout">ログアウト</button>
    </form>
</x-layouts.mobile>
